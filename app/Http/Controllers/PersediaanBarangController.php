<?php

namespace App\Http\Controllers;

use App\InventoryBrand;
use App\InventoryCondition;
use App\InventoryItem;
use App\InventoryItemDetail;
use App\InventoryRoom;
use App\InventoryUnit;
use App\Services\Inventory\InventoryStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PersediaanBarangController extends Controller
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

        $query = InventoryItem::withCount('details')
            ->orderBy('name');

        if ($request->filled('q')) {
            $keyword = trim($request->q);
            $query->where(function ($builder) use ($keyword) {
                $builder->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('code', 'like', '%' . $keyword . '%');
            });
        }

        $items = $query->paginate(12)->appends($request->only('q'));

        return view('persediaan.items.index', compact('items'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->canManageInventoryTransactions(), 403);

        $validated = $request->validate([
            'code' => 'required|string|max:100|unique:inventory_items,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['created_by'] = auth()->id();

        InventoryItem::create($validated);

        return redirect()->route('perawatan-alat-mesin.items.index')->with('success', 'Barang induk berhasil ditambahkan.');
    }

    public function show(InventoryItem $inventoryItem)
    {
        abort_unless(auth()->user()->canAccessInventoryModule(), 403);

        $inventoryItem->load([
            'details.condition',
            'details.unit',
            'details.room',
            'details.brand',
            'maintenanceTransactions.attachments',
            'maintenanceTransactions.detail',
        ]);

        $unitOptions = InventoryUnit::orderBy('name')->get();
        $conditionOptions = InventoryCondition::orderBy('name')->get();
        $roomOptions = InventoryRoom::orderBy('name')->get();
        $brandOptions = InventoryBrand::orderBy('name')->get();

        $maintenanceTotal = (float) $inventoryItem->maintenanceTransactions->sum('amount');

        return view('persediaan.items.show', compact(
            'inventoryItem',
            'unitOptions',
            'conditionOptions',
            'roomOptions',
            'brandOptions',
            'maintenanceTotal'
        ));
    }

    public function update(Request $request, InventoryItem $inventoryItem)
    {
        abort_unless(auth()->user()->canManageInventoryTransactions(), 403);

        $validated = $request->validate([
            'code' => 'required|string|max:100|unique:inventory_items,code,' . $inventoryItem->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $inventoryItem->update($validated);

        return redirect()->route('perawatan-alat-mesin.items.show', $inventoryItem)->with('success', 'Barang induk berhasil diperbarui.');
    }

    public function storeDetail(Request $request, InventoryItem $inventoryItem)
    {
        abort_unless(auth()->user()->canManageInventoryTransactions(), 403);

        $validated = $request->validate([
            'sub_code' => 'nullable|string|max:120|unique:inventory_item_details,sub_code',
            'nup' => 'nullable|string|max:120',
            'name' => 'required|string|max:255',
            'acquisition_date' => 'nullable|date',
            'acquisition_value' => 'nullable|numeric|min:0',
            'inventory_unit_id' => 'nullable|exists:inventory_units,id',
            'inventory_condition_id' => 'nullable|exists:inventory_conditions,id',
            'inventory_room_id' => 'nullable|exists:inventory_rooms,id',
            'inventory_brand_id' => 'nullable|exists:inventory_brands,id',
            'notes' => 'nullable|string',
            'photo' => 'nullable|image|max:4096',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['inventory_item_id'] = $inventoryItem->id;
        $validated['acquisition_value'] = $request->filled('acquisition_value') ? $request->acquisition_value : 0;
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['sub_code'] = $validated['sub_code'] ?: $this->generateSubCode($inventoryItem);

        if ($request->hasFile('photo')) {
            $stored = $this->storage->storePhoto($request->file('photo'));
            $validated['photo_path'] = $stored['path'];
            $validated['photo_original_name'] = $stored['original_name'];
        }

        InventoryItemDetail::create($validated);

        return redirect()->route('perawatan-alat-mesin.items.show', $inventoryItem)->with('success', 'Sub barang berhasil ditambahkan.');
    }

    public function updateDetail(Request $request, InventoryItem $inventoryItem, InventoryItemDetail $inventoryItemDetail)
    {
        abort_unless(auth()->user()->canManageInventoryTransactions(), 403);
        abort_unless((int) $inventoryItemDetail->inventory_item_id === (int) $inventoryItem->id, 404);

        $validated = $request->validate([
            'sub_code' => 'required|string|max:120|unique:inventory_item_details,sub_code,' . $inventoryItemDetail->id,
            'nup' => 'nullable|string|max:120',
            'name' => 'required|string|max:255',
            'acquisition_date' => 'nullable|date',
            'acquisition_value' => 'nullable|numeric|min:0',
            'inventory_unit_id' => 'nullable|exists:inventory_units,id',
            'inventory_condition_id' => 'nullable|exists:inventory_conditions,id',
            'inventory_room_id' => 'nullable|exists:inventory_rooms,id',
            'inventory_brand_id' => 'nullable|exists:inventory_brands,id',
            'notes' => 'nullable|string',
            'photo' => 'nullable|image|max:4096',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['acquisition_value'] = $request->filled('acquisition_value') ? $request->acquisition_value : 0;
        $validated['is_active'] = $request->boolean('is_active', true);

        if ($request->hasFile('photo')) {
            $stored = $this->storage->replacePublicFile($inventoryItemDetail->photo_path, $request->file('photo'), 'photo');
            $validated['photo_path'] = $stored['path'];
            $validated['photo_original_name'] = $stored['original_name'];
        }

        $inventoryItemDetail->update($validated);

        return redirect()->route('perawatan-alat-mesin.items.show', $inventoryItem)->with('success', 'Sub barang berhasil diperbarui.');
    }

    public function photo(InventoryItemDetail $inventoryItemDetail)
    {
        abort_unless(auth()->user()->canAccessInventoryModule(), 403);

        $path = $this->storage->absolutePath($inventoryItemDetail->photo_path);
        abort_unless($path, 404);

        return response()->file($path);
    }

    protected function generateSubCode(InventoryItem $item)
    {
        $lastDetail = $item->details()->orderByDesc('id')->first();
        $lastNumber = 0;

        if ($lastDetail && preg_match('/(\d+)$/', $lastDetail->sub_code, $matches)) {
            $lastNumber = (int) $matches[1];
        }

        return $item->code . '.' . str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
    }
}
