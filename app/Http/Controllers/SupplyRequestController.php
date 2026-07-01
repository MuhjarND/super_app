<?php

namespace App\Http\Controllers;

use App\Services\SignaturePadService;
use App\Services\WhatsAppNotificationService;
use App\SupplyItem;
use App\SupplyPickup;
use App\SupplyRequest;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class SupplyRequestController extends Controller
{
    protected $whatsAppService;
    protected $signaturePadService;

    public function __construct(WhatsAppNotificationService $whatsAppService, SignaturePadService $signaturePadService)
    {
        $this->middleware('auth');
        $this->whatsAppService = $whatsAppService;
        $this->signaturePadService = $signaturePadService;
    }

    public function index(Request $request)
    {
        abort_unless(auth()->user()->canAccessSupplyModule(), 403);

        if (!$this->moduleReady()) {
            return response()->view('persediaan.supplies.setup');
        }

        $user = auth()->user();
        $canManage = $user->canManageSupplyModule();
        $query = SupplyRequest::with(['requester', 'items'])->latest('id');

        if (!$canManage) {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->paginate(15);

        return view('persediaan.supplies.requests.index', compact('requests', 'canManage'));
    }

    public function create(Request $request)
    {
        abort_unless(auth()->user()->canAccessSupplyModule(), 403);

        if (!$this->moduleReady()) {
            return response()->view('persediaan.supplies.setup');
        }

        $items = SupplyItem::active()->orderBy('name')->get();
        $selectedItemId = $request->query('item');

        return view('persediaan.supplies.requests.create', compact('items', 'selectedItemId'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->canAccessSupplyModule(), 403);

        $request->validate([
            'purpose' => ['required', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
        ]);

        $items = $this->normalizeItems($request);

        $supplyRequest = DB::transaction(function () use ($request, $items) {
            $supplyRequest = SupplyRequest::create([
                'request_number' => $this->generateRequestNumber(),
                'user_id' => auth()->id(),
                'status' => SupplyRequest::STATUS_PENDING,
                'purpose' => $request->input('purpose'),
                'submitted_at' => Carbon::now('Asia/Jayapura'),
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            foreach ($items as $item) {
                $supplyRequest->items()->create($item);
            }

            return $supplyRequest->fresh(['requester', 'items']);
        });

        $this->notifyOperators($supplyRequest);

        return redirect()->route('persediaan.requests.index')->with('success', 'Pengajuan persediaan berhasil dikirim ke operator.');
    }

    public function show(SupplyRequest $supplyRequest)
    {
        abort_unless(auth()->user()->canAccessSupplyModule(), 403);
        $this->authorizeView($supplyRequest);

        $supplyRequest->load(['requester', 'processor', 'items.item', 'pickups']);
        $canManage = auth()->user()->canManageSupplyModule();

        return view('persediaan.supplies.requests.show', compact('supplyRequest', 'canManage'));
    }

    public function fulfill(Request $request, SupplyRequest $supplyRequest)
    {
        abort_unless(auth()->user()->canManageSupplyModule(), 403);
        abort_unless($supplyRequest->status === SupplyRequest::STATUS_PENDING, 422, 'Pengajuan ini sudah tidak dapat diproses.');

        $request->validate([
            'signature_data' => ['required', 'string'],
            'operator_note' => ['nullable', 'string', 'max:1000'],
        ], [
            'signature_data.required' => 'Paraf penerima wajib diisi sebelum barang diserahkan.',
        ]);

        $supplyRequest->load(['requester', 'items.item']);
        $this->ensureStockAvailable($supplyRequest);

        $signature = $this->signaturePadService->storeDataUri($request->input('signature_data'), 'persediaan/paraf-penerima');

        DB::transaction(function () use ($supplyRequest, $request, $signature) {
            $freshRequest = SupplyRequest::whereKey($supplyRequest->id)->lockForUpdate()->firstOrFail();
            if ($freshRequest->status !== SupplyRequest::STATUS_PENDING) {
                throw ValidationException::withMessages(['status' => 'Pengajuan ini sudah tidak dapat diproses.']);
            }

            $items = $freshRequest->items()->with('item')->get();

            foreach ($items as $requestItem) {
                if ($requestItem->item) {
                    $item = SupplyItem::whereKey($requestItem->item->id)->lockForUpdate()->firstOrFail();

                    if ((int) $item->stock < (int) $requestItem->quantity_requested) {
                        throw ValidationException::withMessages([
                            'stock' => 'Stok ' . $item->name . ' tidak mencukupi untuk diserahkan.',
                        ]);
                    }

                    $item->decrement('stock', (int) $requestItem->quantity_requested);
                }

                $requestItem->update([
                    'quantity_fulfilled' => (int) $requestItem->quantity_requested,
                ]);

                SupplyPickup::create([
                    'supply_request_id' => $freshRequest->id,
                    'supply_request_item_id' => $requestItem->id,
                    'supply_item_id' => $requestItem->supply_item_id,
                    'user_id' => $freshRequest->user_id,
                    'item_name_snapshot' => $requestItem->item_name_snapshot,
                    'unit_snapshot' => $requestItem->unit_snapshot,
                    'quantity' => (int) $requestItem->quantity_requested,
                    'purpose' => $freshRequest->purpose,
                    'pickup_date' => Carbon::now('Asia/Jayapura')->toDateString(),
                    'receiver_signature_path' => $signature['path'],
                    'receiver_signature_mime' => $signature['mime'],
                    'receiver_signature_size' => $signature['size'],
                    'created_by' => auth()->id(),
                ]);
            }

            $freshRequest->update([
                'status' => SupplyRequest::STATUS_FULFILLED,
                'operator_note' => $request->input('operator_note'),
                'processed_by' => auth()->id(),
                'processed_at' => Carbon::now('Asia/Jayapura'),
                'fulfilled_at' => Carbon::now('Asia/Jayapura'),
                'updated_by' => auth()->id(),
            ]);
        });

        $this->whatsAppService->notifySupplyRequestStatus(
            $supplyRequest->fresh(['requester', 'items']),
            $supplyRequest->requester,
            'Pengajuan persediaan telah diproses',
            'Barang persediaan telah diserahkan dan tercatat pada aplikasi.'
        );

        return redirect()->route('persediaan.pickups.index')->with('success', 'Barang berhasil diserahkan dan paraf penerima tersimpan.');
    }

    public function reject(Request $request, SupplyRequest $supplyRequest)
    {
        abort_unless(auth()->user()->canManageSupplyModule(), 403);
        abort_unless($supplyRequest->status === SupplyRequest::STATUS_PENDING, 422, 'Pengajuan ini sudah tidak dapat diproses.');

        $validated = $request->validate([
            'operator_note' => ['required', 'string', 'max:1000'],
        ]);

        $supplyRequest->update([
            'status' => SupplyRequest::STATUS_REJECTED,
            'operator_note' => $validated['operator_note'],
            'processed_by' => auth()->id(),
            'processed_at' => Carbon::now('Asia/Jayapura'),
            'updated_by' => auth()->id(),
        ]);

        $supplyRequest->load(['requester', 'items']);
        $this->whatsAppService->notifySupplyRequestStatus(
            $supplyRequest,
            $supplyRequest->requester,
            'Pengajuan persediaan belum dapat diproses',
            'Catatan operator: ' . $validated['operator_note']
        );

        return redirect()->route('persediaan.requests.index')->with('success', 'Pengajuan persediaan ditolak dengan catatan operator.');
    }

    public function cancel(SupplyRequest $supplyRequest)
    {
        abort_unless(auth()->user()->canAccessSupplyModule(), 403);
        abort_unless((int) $supplyRequest->user_id === (int) auth()->id(), 403);
        abort_unless($supplyRequest->status === SupplyRequest::STATUS_PENDING, 422, 'Pengajuan ini sudah tidak dapat dibatalkan.');

        $supplyRequest->update([
            'status' => SupplyRequest::STATUS_CANCELLED,
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('persediaan.requests.index')->with('success', 'Pengajuan persediaan berhasil dibatalkan.');
    }

    protected function normalizeItems(Request $request)
    {
        $rows = collect($request->input('items', []));
        $normalized = [];
        $errors = [];

        foreach ($rows as $index => $row) {
            $selector = $row['supply_item_id'] ?? null;
            $quantity = (int) ($row['quantity'] ?? 0);
            $customName = trim((string) ($row['custom_item_name'] ?? ''));

            if ($quantity < 1 && (is_numeric($selector) || $customName === '')) {
                continue;
            }

            if ($quantity < 1) {
                $errors["items.$index.quantity"] = 'Jumlah barang wajib minimal 1.';
                continue;
            }

            if (is_numeric($selector)) {
                $item = SupplyItem::active()->find($selector);
                if (!$item) {
                    $errors["items.$index.supply_item_id"] = 'Barang persediaan tidak valid.';
                    continue;
                }

                $normalized[] = [
                    'supply_item_id' => $item->id,
                    'item_name_snapshot' => $item->name,
                    'unit_snapshot' => $item->unit,
                    'quantity_requested' => $quantity,
                ];

                continue;
            }

            if ($customName === '') {
                $errors["items.$index.custom_item_name"] = 'Nama barang wajib diisi jika barang belum tersedia di daftar kantor.';
                continue;
            }

            $normalized[] = [
                'supply_item_id' => null,
                'item_name_snapshot' => $customName,
                'unit_snapshot' => trim((string) ($row['custom_unit'] ?? 'Pcs')) ?: 'Pcs',
                'quantity_requested' => $quantity,
            ];
        }

        if (empty($normalized)) {
            $errors['items'] = 'Minimal satu barang harus diajukan.';
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        return $normalized;
    }

    protected function generateRequestNumber()
    {
        $prefix = 'PS-' . now('Asia/Jayapura')->format('Ymd');
        $next = SupplyRequest::where('request_number', 'like', $prefix . '-%')->count() + 1;

        return $prefix . '-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    protected function ensureStockAvailable(SupplyRequest $supplyRequest)
    {
        foreach ($supplyRequest->items as $requestItem) {
            if (!$requestItem->item) {
                continue;
            }

            if ((int) $requestItem->item->stock < (int) $requestItem->quantity_requested) {
                throw ValidationException::withMessages([
                    'stock' => 'Stok ' . $requestItem->item->name . ' hanya tersedia ' . $requestItem->item->stock_label . '.',
                ]);
            }
        }
    }

    protected function notifyOperators(SupplyRequest $supplyRequest)
    {
        $operators = User::whereHas('roles', function ($query) {
            $query->where('name', 'operator_persediaan');
        })->active()->get()->unique('id')->values();

        if ($operators->isEmpty()) {
            $operators = User::whereHas('roles', function ($query) {
                $query->where('name', 'super_admin');
            })->active()->get()->unique('id')->values();
        }

        foreach ($operators as $operator) {
            $this->whatsAppService->notifySupplyRequestSubmitted($supplyRequest, $operator);
        }
    }

    protected function authorizeView(SupplyRequest $supplyRequest)
    {
        if (auth()->user()->canManageSupplyModule()) {
            return;
        }

        abort_unless((int) $supplyRequest->user_id === (int) auth()->id(), 403);
    }

    protected function moduleReady()
    {
        return Schema::hasTable('supply_items') && Schema::hasTable('supply_requests');
    }
}
