<?php

namespace App\Http\Controllers;

use App\InventoryItem;
use App\InventoryItemDetail;
use App\InventoryMaintenanceSchedule;
use Illuminate\Http\Request;

class PersediaanJadwalPerawatanController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        abort_unless(auth()->user()->canAccessInventoryModule(), 403);

        $query = InventoryMaintenanceSchedule::with([
            'item',
            'detail.room',
            'creator',
            'notifications.user.jabatan',
        ])->latest('scheduled_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('q')) {
            $keyword = trim($request->q);
            $query->where(function ($builder) use ($keyword) {
                $builder->where('description', 'like', '%' . $keyword . '%')
                    ->orWhereHas('item', function ($itemQuery) use ($keyword) {
                        $itemQuery->where('name', 'like', '%' . $keyword . '%')
                            ->orWhere('code', 'like', '%' . $keyword . '%');
                    })->orWhereHas('detail', function ($detailQuery) use ($keyword) {
                        $detailQuery->where('name', 'like', '%' . $keyword . '%')
                            ->orWhere('sub_code', 'like', '%' . $keyword . '%')
                            ->orWhere('nup', 'like', '%' . $keyword . '%');
                    });
            });
        }

        if ($request->filled('focus')) {
            $query->orderByRaw('CASE WHEN inventory_maintenance_schedules.id = ? THEN 0 ELSE 1 END', [(int) $request->focus]);
        }

        $schedules = $query->paginate(20)->appends($request->only('q', 'status', 'focus'));
        $items = InventoryItem::with(['details' => function ($detailQuery) {
            $detailQuery->where('is_active', true)->orderBy('sub_code');
        }])->where('is_active', true)->orderBy('name')->get();
        $canSchedule = auth()->user()->canScheduleInventoryMaintenance();

        return view('persediaan.schedules.index', compact('schedules', 'items', 'canSchedule'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->canScheduleInventoryMaintenance(), 403);

        $validated = $this->validateSchedule($request);
        $validated['status'] = 'scheduled';
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        InventoryMaintenanceSchedule::create($validated);

        return redirect()->route('perawatan-alat-mesin.schedules.index')
            ->with('success', 'Jadwal perawatan berhasil dibuat.');
    }

    public function update(Request $request, InventoryMaintenanceSchedule $inventoryMaintenanceSchedule)
    {
        abort_unless(auth()->user()->canScheduleInventoryMaintenance(), 403);
        abort_if($inventoryMaintenanceSchedule->status !== 'scheduled', 422, 'Jadwal yang selesai atau dibatalkan tidak dapat diubah.');

        $validated = $this->validateSchedule($request);
        $validated['updated_by'] = auth()->id();

        $inventoryMaintenanceSchedule->update($validated);
        $inventoryMaintenanceSchedule->notifications()->delete();
        $inventoryMaintenanceSchedule->update(['notification_completed_at' => null]);

        return redirect()->route('perawatan-alat-mesin.schedules.index')
            ->with('success', 'Jadwal perawatan berhasil diperbarui.');
    }

    public function complete(InventoryMaintenanceSchedule $inventoryMaintenanceSchedule)
    {
        abort_unless(auth()->user()->canScheduleInventoryMaintenance(), 403);
        abort_if($inventoryMaintenanceSchedule->status !== 'scheduled', 422, 'Hanya jadwal aktif yang dapat diselesaikan.');

        $inventoryMaintenanceSchedule->update([
            'status' => 'completed',
            'completed_at' => now(),
            'completed_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return back()->with('success', 'Jadwal ditandai selesai.');
    }

    public function cancel(InventoryMaintenanceSchedule $inventoryMaintenanceSchedule)
    {
        abort_unless(auth()->user()->canScheduleInventoryMaintenance(), 403);
        abort_if($inventoryMaintenanceSchedule->status !== 'scheduled', 422, 'Hanya jadwal aktif yang dapat dibatalkan.');

        $inventoryMaintenanceSchedule->update([
            'status' => 'cancelled',
            'updated_by' => auth()->id(),
        ]);

        return back()->with('success', 'Jadwal perawatan dibatalkan.');
    }

    public function destroy(InventoryMaintenanceSchedule $inventoryMaintenanceSchedule)
    {
        abort_unless(auth()->user()->canScheduleInventoryMaintenance(), 403);

        $inventoryMaintenanceSchedule->delete();

        return back()->with('success', 'Jadwal perawatan berhasil dihapus.');
    }

    protected function validateSchedule(Request $request)
    {
        $validated = $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'inventory_item_detail_id' => 'nullable|exists:inventory_item_details,id',
            'scheduled_at' => 'required|date',
            'description' => 'required|string|max:2000',
        ]);

        if (!empty($validated['inventory_item_detail_id'])) {
            $detail = InventoryItemDetail::findOrFail($validated['inventory_item_detail_id']);
            abort_unless(
                (int) $detail->inventory_item_id === (int) $validated['inventory_item_id'],
                422,
                'Sub barang tidak sesuai dengan barang induk.'
            );
        }

        return $validated;
    }
}
