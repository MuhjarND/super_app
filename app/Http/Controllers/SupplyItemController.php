<?php

namespace App\Http\Controllers;

use App\SupplyItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class SupplyItemController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        abort_unless(auth()->user()->canManageSupplyModule(), 403);

        if (!Schema::hasTable('supply_items')) {
            return response()->view('persediaan.supplies.setup');
        }

        $items = SupplyItem::orderBy('name')->paginate(20);

        return view('persediaan.supplies.items.index', compact('items'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->canManageSupplyModule(), 403);

        $validated = $this->validatePayload($request);
        $validated['is_active'] = $request->has('is_active');
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('persediaan/items', 'public');
        }
        unset($validated['image']);

        SupplyItem::create($validated);

        return redirect()->route('persediaan.items.index')->with('success', 'Barang persediaan berhasil ditambahkan.');
    }

    public function update(Request $request, SupplyItem $supplyItem)
    {
        abort_unless(auth()->user()->canManageSupplyModule(), 403);

        $validated = $this->validatePayload($request, $supplyItem->id);
        $validated['is_active'] = $request->has('is_active');
        $validated['updated_by'] = auth()->id();

        if ($request->hasFile('image')) {
            if ($supplyItem->image_path) {
                Storage::disk('public')->delete($supplyItem->image_path);
            }

            $validated['image_path'] = $request->file('image')->store('persediaan/items', 'public');
        }
        unset($validated['image']);

        $supplyItem->update($validated);

        return redirect()->route('persediaan.items.index')->with('success', 'Barang persediaan berhasil diperbarui.');
    }

    protected function validatePayload(Request $request, $ignoreId = null)
    {
        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:80', Rule::unique('supply_items', 'code')->ignore($ignoreId)],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:50'],
            'stock' => ['required', 'integer', 'min:0'],
            'minimum_stock' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $validated['minimum_stock'] = $validated['minimum_stock'] ?? 0;

        return $validated;
    }
}
