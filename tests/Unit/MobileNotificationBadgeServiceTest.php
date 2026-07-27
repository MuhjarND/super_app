<?php

namespace Tests\Unit;

use App\Services\MobileNotificationBadgeService;
use App\User;
use Mockery;
use Tests\TestCase;

class MobileNotificationBadgeServiceTest extends TestCase
{
    public function test_it_maps_action_items_to_mobile_module_and_submodule_badges()
    {
        $payload = [
            'items' => [
                [
                    'id' => 'persuratan-disposisi-10',
                    'module_key' => 'persuratan',
                    'type_key' => 'surat_masuk',
                    'type_label' => 'Tindak Lanjut Disposisi',
                ],
                [
                    'id' => 'rapat-follow-up-11',
                    'module_key' => 'rapat',
                    'type_key' => 'rapat',
                    'type_label' => 'Tindak Lanjut Rapat',
                ],
                [
                    'id' => 'cuti-approval-12',
                    'module_key' => 'cuti',
                    'type_key' => 'cuti',
                    'type_label' => 'Approval Cuti',
                ],
            ],
            'summary' => [
                'active_count' => 3,
                'module_counts' => [
                    'persuratan' => 1,
                    'rapat' => 1,
                    'cuti' => 1,
                ],
            ],
        ];

        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 999;
        $user->shouldReceive('canAccessSupplyModule')->andReturn(false);
        $user->shouldReceive('canManageSupplyModule')->andReturn(false);
        $user->shouldReceive('canAccessLibraryModule')->andReturn(false);
        $user->shouldReceive('canManageLibraryModule')->andReturn(false);

        $badges = (new MobileNotificationBadgeService())->build($user, $payload);

        $this->assertSame(3, $badges['modules']['action']);
        $this->assertSame(1, $badges['modules']['persuratan']);
        $this->assertSame(1, $badges['submodules']['persuratan']['surat_masuk']);
        $this->assertSame(1, $badges['submodules']['rapat']['tindak_lanjut']);
        $this->assertSame(1, $badges['submodules']['cuti']['approval']);
    }
}
