<?php

namespace Tests\Unit;

use App\Services\UnifiedActionCenterService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class UnifiedActionCenterServiceTest extends TestCase
{
    public function test_items_are_sorted_by_latest_activity_regardless_of_priority()
    {
        $service = new TestableUnifiedActionCenterService();
        $items = collect([
            $service->normalize([
                'id' => 'older-high-priority',
                'module_key' => 'cuti',
                'module_label' => 'Cuti',
                'type_label' => 'Approval Cuti',
                'title' => 'Item lama',
                'priority_key' => 'high',
                'status_key' => 'overdue',
                'is_overdue' => true,
                'sort_at' => 100,
            ]),
            $service->normalize([
                'id' => 'newer-normal-priority',
                'module_key' => 'rapat',
                'module_label' => 'Rapat',
                'type_label' => 'Approval Rapat',
                'title' => 'Item terbaru',
                'priority_key' => 'normal',
                'status_key' => 'waiting',
                'is_overdue' => false,
                'sort_at' => 200,
            ]),
        ]);

        $sorted = $service->sort($items)->values();

        $this->assertSame('newer-normal-priority', $sorted->first()['id']);
    }

    public function test_persuratan_items_are_split_into_incoming_and_outgoing_filters()
    {
        $service = new TestableUnifiedActionCenterService();
        $incoming = $service->normalize([
            'id' => 'persuratan-disposisi-15',
            'module_key' => 'persuratan',
            'module_label' => 'Persuratan',
            'type_label' => 'Tindak Lanjut Disposisi',
            'title' => 'Surat masuk',
        ]);
        $outgoing = $service->normalize([
            'id' => 'persuratan-draft-20',
            'module_key' => 'persuratan',
            'module_label' => 'Persuratan',
            'type_label' => 'Draft Surat Keluar',
            'title' => 'Surat keluar',
        ]);

        $this->assertSame('surat_masuk', $incoming['type_key']);
        $this->assertSame('surat_keluar', $outgoing['type_key']);

        $filtered = $service->filter(collect([$incoming, $outgoing]), [
            'module' => 'all',
            'type' => 'surat_masuk',
            'status' => 'all',
            'unit' => 'all',
            'assignee' => 'all',
            'search' => '',
        ]);

        $this->assertCount(1, $filtered);
        $this->assertSame('persuratan-disposisi-15', $filtered->first()['id']);
    }
}

class TestableUnifiedActionCenterService extends UnifiedActionCenterService
{
    public function normalize(array $item)
    {
        return $this->normalizeItem($item);
    }

    public function sort(Collection $items)
    {
        return $this->sortItems($items);
    }

    public function filter(Collection $items, array $filters)
    {
        return $this->applyBaseFilters($items, $filters);
    }
}
