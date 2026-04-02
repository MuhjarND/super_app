<?php

namespace App\Http\Controllers;

use App\InventoryAuthority;
use App\InventoryBrand;
use App\InventoryCondition;
use App\InventoryRoom;
use App\InventoryUnit;
use Illuminate\Http\Request;

class PersediaanMasterDataController extends Controller
{
    protected $masters = [
        'units' => [
            'model' => InventoryUnit::class,
            'title' => 'Master Satuan Barang',
            'name_label' => 'Nama Satuan',
            'description_label' => 'Keterangan',
        ],
        'conditions' => [
            'model' => InventoryCondition::class,
            'title' => 'Master Kondisi Barang',
            'name_label' => 'Nama Kondisi',
            'description_label' => 'Keterangan',
        ],
        'rooms' => [
            'model' => InventoryRoom::class,
            'title' => 'Master Ruang',
            'name_label' => 'Nama Ruang',
            'description_label' => 'Keterangan',
        ],
        'brands' => [
            'model' => InventoryBrand::class,
            'title' => 'Master Merk / Brand',
            'name_label' => 'Nama Merk',
            'description_label' => 'Keterangan',
        ],
    ];

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index($type)
    {
        abort_unless(auth()->user()->canManageInventoryMasterData(), 403);

        $meta = $this->resolveMaster($type);
        $rows = $meta['model']::orderBy('name')->get();

        return view('persediaan.master.index', compact('type', 'meta', 'rows'));
    }

    public function store(Request $request, $type)
    {
        abort_unless(auth()->user()->canManageInventoryMasterData(), 403);

        $meta = $this->resolveMaster($type);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
        ]);

        $meta['model']::create($validated);

        return redirect()->route('perawatan-alat-mesin.master.index', $type)->with('success', 'Master data berhasil ditambahkan.');
    }

    public function update(Request $request, $type, $id)
    {
        abort_unless(auth()->user()->canManageInventoryMasterData(), 403);

        $meta = $this->resolveMaster($type);
        $row = $meta['model']::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
        ]);

        $row->update($validated);

        return redirect()->route('perawatan-alat-mesin.master.index', $type)->with('success', 'Master data berhasil diperbarui.');
    }

    public function destroy($type, $id)
    {
        abort_unless(auth()->user()->canManageInventoryMasterData(), 403);

        $meta = $this->resolveMaster($type);
        $row = $meta['model']::findOrFail($id);
        $row->delete();

        return redirect()->route('perawatan-alat-mesin.master.index', $type)->with('success', 'Master data berhasil dihapus.');
    }

    public function authority()
    {
        abort_unless(auth()->user()->canManageInventoryMasterData(), 403);

        $authority = InventoryAuthority::latest()->first();

        return view('persediaan.master.authority', compact('authority'));
    }

    public function storeAuthority(Request $request)
    {
        abort_unless(auth()->user()->canManageInventoryMasterData(), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'nip' => 'nullable|string|max:100',
            'position' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        InventoryAuthority::updateOrCreate(
            ['id' => $request->input('id')],
            $validated
        );

        return redirect()->route('perawatan-alat-mesin.authority.index')->with('success', 'Data kuasa pengguna barang berhasil disimpan.');
    }

    protected function resolveMaster($type)
    {
        abort_unless(isset($this->masters[$type]), 404);

        return $this->masters[$type];
    }
}
