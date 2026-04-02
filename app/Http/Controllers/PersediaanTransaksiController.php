<?php

namespace App\Http\Controllers;

use App\InventoryItem;
use App\InventoryItemDetail;
use App\InventoryMaintenanceTransaction;
use App\InventoryTransactionAttachment;
use App\Services\Inventory\InventoryStorageService;
use Illuminate\Http\Request;

class PersediaanTransaksiController extends Controller
{
    protected $storage;

    public function __construct(InventoryStorageService $storage)
    {
        $this->middleware('auth');
        $this->storage = $storage;
    }

    public function index(Request $request)
    {
        abort_unless(auth()->user()->canAccessInventoryModule(), 403);

        $query = InventoryMaintenanceTransaction::with(['item', 'detail', 'attachments', 'creator'])
            ->latest('transaction_date');

        if ($request->filled('from') && $request->filled('to')) {
            $query->whereBetween('transaction_date', [$request->from, $request->to]);
        }

        if ($request->filled('inventory_item_id')) {
            $query->where('inventory_item_id', $request->inventory_item_id);
        }

        if ($request->filled('inventory_item_detail_id')) {
            $query->where('inventory_item_detail_id', $request->inventory_item_detail_id);
        }

        $items = InventoryItem::orderBy('name')->get();
        $details = InventoryItemDetail::with('item')->orderBy('sub_code')->get();
        $filteredTransactions = (clone $query)->get();

        $itemQuery = InventoryItem::with(['details' => function ($detailQuery) {
            $detailQuery->with(['unit', 'condition', 'room', 'brand']);
        }])->orderBy('code');

        if ($request->filled('inventory_item_id')) {
            $itemQuery->where('id', $request->inventory_item_id);
        }

        $itemCollection = $itemQuery->get();
        $transactionsByItem = $filteredTransactions->groupBy('inventory_item_id');

        $groupedItems = $itemCollection->map(function ($item) use ($transactionsByItem) {
            $itemTransactions = $transactionsByItem->get($item->id, collect())->sortByDesc('transaction_date')->values();

            $detailRows = $item->details->map(function ($detail) use ($itemTransactions) {
                $detailTransactions = $itemTransactions->where('inventory_item_detail_id', $detail->id)->values();

                return [
                    'detail' => $detail,
                    'subtotal' => (float) $detailTransactions->sum('amount'),
                    'transaction_count' => $detailTransactions->count(),
                    'transactions' => $detailTransactions,
                ];
            })->filter(function ($row) use ($itemTransactions) {
                return $row['transaction_count'] > 0 || $itemTransactions->isEmpty();
            })->sort(function ($left, $right) {
                if ($left['transaction_count'] !== $right['transaction_count']) {
                    return $right['transaction_count'] <=> $left['transaction_count'];
                }

                if ($left['subtotal'] !== $right['subtotal']) {
                    return $right['subtotal'] <=> $left['subtotal'];
                }

                $leftCode = optional($left['detail'])->sub_code ?: '';
                $rightCode = optional($right['detail'])->sub_code ?: '';

                return strnatcasecmp($leftCode, $rightCode);
            })->values();

            $directTransactions = $itemTransactions->whereNull('inventory_item_detail_id')->values();
            if ($directTransactions->isNotEmpty()) {
                $detailRows->push([
                    'detail' => null,
                    'subtotal' => (float) $directTransactions->sum('amount'),
                    'transaction_count' => $directTransactions->count(),
                    'transactions' => $directTransactions,
                ]);
            }

            return [
                'item' => $item,
                'detail_rows' => $detailRows,
                'detail_count' => $item->details->count(),
                'transaction_count' => $itemTransactions->count(),
                'subtotal' => (float) $itemTransactions->sum('amount'),
                'is_open' => $itemTransactions->isNotEmpty(),
            ];
        })->filter(function ($row) use ($request) {
            if ($request->filled('inventory_item_id')) {
                return true;
            }

            if ($request->filled('from') || $request->filled('to') || $request->filled('inventory_item_detail_id')) {
                return $row['transaction_count'] > 0;
            }

            return true;
        })->sort(function ($left, $right) {
            if ($left['transaction_count'] !== $right['transaction_count']) {
                return $right['transaction_count'] <=> $left['transaction_count'];
            }

            if ($left['subtotal'] !== $right['subtotal']) {
                return $right['subtotal'] <=> $left['subtotal'];
            }

            return strnatcasecmp($left['item']->code ?: '', $right['item']->code ?: '');
        })->values();

        $expandedRow = $groupedItems->first(function ($row) {
            return $row['transaction_count'] > 0;
        }) ?: $groupedItems->first();
        $expandedItemId = $expandedRow ? $expandedRow['item']->id : null;

        return view('persediaan.maintenance.index', compact(
            'items',
            'details',
            'groupedItems',
            'expandedItemId'
        ));
    }

    public function show(InventoryItemDetail $inventoryItemDetail)
    {
        abort_unless(auth()->user()->canAccessInventoryModule(), 403);

        $inventoryItemDetail->load([
            'item',
            'unit',
            'condition',
            'room',
            'brand',
            'maintenanceTransactions.attachments',
            'maintenanceTransactions.creator',
        ]);

        $items = InventoryItem::orderBy('name')->get();
        $details = InventoryItemDetail::with('item')->orderBy('sub_code')->get();
        $transactionTotal = (float) $inventoryItemDetail->maintenanceTransactions->sum('amount');

        return view('persediaan.maintenance.show', compact(
            'inventoryItemDetail',
            'items',
            'details',
            'transactionTotal'
        ));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->canManageInventoryTransactions(), 403);

        $validated = $this->validateTransaction($request);

        $validated['source_type'] = 'manual';
        $validated['status'] = 'completed';
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        $transaction = InventoryMaintenanceTransaction::create($validated);

        foreach ((array) $request->file('attachments', []) as $file) {
            if (!$file) {
                continue;
            }

            $stored = $this->storage->storeAttachment($file);
            $transaction->attachments()->create([
                'file_path' => $stored['path'],
                'original_name' => $stored['original_name'],
                'mime_type' => $stored['mime_type'],
                'file_size' => $stored['size'],
                'uploaded_by' => auth()->id(),
            ]);
        }

        return $this->redirectAfterMutation($request, 'Transaksi perawatan berhasil disimpan.');
    }

    public function update(Request $request, InventoryMaintenanceTransaction $inventoryMaintenanceTransaction)
    {
        abort_unless(auth()->user()->canManageInventoryTransactions(), 403);

        $validated = $this->validateTransaction($request);
        $validated['updated_by'] = auth()->id();

        $inventoryMaintenanceTransaction->update($validated);

        foreach ((array) $request->file('attachments', []) as $file) {
            if (!$file) {
                continue;
            }

            $stored = $this->storage->storeAttachment($file);
            $inventoryMaintenanceTransaction->attachments()->create([
                'file_path' => $stored['path'],
                'original_name' => $stored['original_name'],
                'mime_type' => $stored['mime_type'],
                'file_size' => $stored['size'],
                'uploaded_by' => auth()->id(),
            ]);
        }

        return $this->redirectAfterMutation($request, 'Transaksi perawatan berhasil diperbarui.');
    }

    public function destroy(Request $request, InventoryMaintenanceTransaction $inventoryMaintenanceTransaction)
    {
        abort_unless(auth()->user()->canManageInventoryTransactions(), 403);

        foreach ($inventoryMaintenanceTransaction->attachments as $attachment) {
            $this->storage->delete($attachment->file_path);
        }

        $inventoryMaintenanceTransaction->delete();

        return $this->redirectAfterMutation($request, 'Transaksi perawatan berhasil dihapus.');
    }

    public function file(InventoryTransactionAttachment $inventoryTransactionAttachment)
    {
        abort_unless(auth()->user()->canAccessInventoryModule(), 403);

        $path = $this->storage->absolutePath($inventoryTransactionAttachment->file_path);
        abort_unless($path, 404);

        return response()->file($path);
    }

    protected function validateTransaction(Request $request)
    {
        $validated = $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'inventory_item_detail_id' => 'nullable|exists:inventory_item_details,id',
            'transaction_date' => 'required|date',
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'attachments.*' => 'nullable|file|max:10240',
        ]);

        if (!empty($validated['inventory_item_detail_id'])) {
            $detail = InventoryItemDetail::findOrFail($validated['inventory_item_detail_id']);
            abort_unless((int) $detail->inventory_item_id === (int) $validated['inventory_item_id'], 422, 'Sub barang tidak sesuai dengan barang induk.');
        }

        return $validated;
    }

    protected function redirectAfterMutation(Request $request, $message)
    {
        if ($request->filled('redirect_detail_id')) {
            $detail = InventoryItemDetail::find($request->redirect_detail_id);
            if ($detail) {
                return redirect()->route('perawatan-alat-mesin.maintenance.show', $detail)->with('success', $message);
            }
        }

        return redirect()->route('perawatan-alat-mesin.maintenance.index', $request->only('from', 'to', 'inventory_item_id', 'inventory_item_detail_id'))->with('success', $message);
    }
}
