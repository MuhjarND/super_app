<?php

namespace App\Services;

use App\Library\Loan;
use App\SupplyRequest;
use App\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class MobileNotificationBadgeService
{
    protected $cache = [];

    public function build(User $user, array $actionPayload = null)
    {
        if (isset($this->cache[$user->id])) {
            return $this->cache[$user->id];
        }

        $actionPayload = $actionPayload ?: app(UnifiedActionCenterService::class)->build($user, ['tab' => 'all']);
        $items = collect($actionPayload['items'] ?? []);
        $moduleCounts = (array) data_get($actionPayload, 'summary.module_counts', []);

        $approvalItems = $items->filter(function ($item) {
            return strpos((string) ($item['id'] ?? ''), 'approval-') !== false
                || stripos((string) ($item['type_label'] ?? ''), 'approval') !== false
                || stripos((string) ($item['type_label'] ?? ''), 'paraf') !== false
                || stripos((string) ($item['type_label'] ?? ''), 'review pimpinan') !== false;
        });

        $supplyCount = $this->supplyCount($user);
        $libraryCount = $this->libraryCount($user);
        $allActionCount = (int) data_get($actionPayload, 'summary.active_count', $items->count());

        $badges = [
            'modules' => [
                'dashboard' => 0,
                'action' => $allActionCount,
                'calendar' => 0,
                'approval' => $approvalItems->count(),
                'persuratan' => (int) ($moduleCounts['persuratan'] ?? 0),
                'rapat' => (int) ($moduleCounts['rapat'] ?? 0),
                'cuti' => (int) ($moduleCounts['cuti'] ?? 0),
                'perawatan' => (int) ($moduleCounts['perawatan'] ?? 0),
                'persediaan' => $supplyCount,
                'perpustakaan' => $libraryCount,
                'zi' => (int) ($moduleCounts['progress_zi'] ?? 0),
                'arsip' => 0,
                'master-data' => 0,
            ],
            'submodules' => [
                'action' => [
                    'inbox_kerja' => $allActionCount,
                    'approval' => $approvalItems->count(),
                ],
                'approval' => [
                    'tindaklanjuti' => $approvalItems->count(),
                    'riwayat' => 0,
                ],
                'persuratan' => [
                    'surat_masuk' => $items->where('type_key', 'surat_masuk')->count(),
                    'surat_keluar' => $items->where('type_key', 'surat_keluar')->count(),
                    'template_surat' => $items->filter(function ($item) {
                        return strpos((string) ($item['id'] ?? ''), 'persuratan-approval-') === 0;
                    })->count(),
                ],
                'rapat' => [
                    'rapat' => $this->countIdPrefix($items, 'rapat-approval-'),
                    'notulensi' => $this->countIdPrefix($items, 'rapat-notulensi-approval-'),
                    'tindak_lanjut' => $this->countIdPrefix($items, 'rapat-follow-up-'),
                    'absensi' => 0,
                    'laporan' => 0,
                    'agenda_pimpinan' => 0,
                    'virtual_meeting' => 0,
                    'e_voting' => 0,
                ],
                'cuti' => [
                    'pengajuan' => $this->countIdPrefix($items, 'cuti-revision-'),
                    'approval' => $this->countIdPrefix($items, 'cuti-approval-'),
                    'rekap_saldo' => 0,
                    'laporan' => 0,
                    'cuti_bersama' => 0,
                ],
                'perawatan' => [
                    'dashboard' => (int) ($moduleCounts['perawatan'] ?? 0),
                    'master_barang' => 0,
                    'transaksi' => (int) ($moduleCounts['perawatan'] ?? 0),
                    'jadwal' => 0,
                    'laporan' => 0,
                    'qr_code' => 0,
                ],
                'persediaan' => [
                    'ajukan_barang' => 0,
                    'pengajuan' => $supplyCount,
                    'barang_diambil' => 0,
                    'master_barang' => $user->canManageSupplyModule() ? $supplyCount : 0,
                ],
                'perpustakaan' => [
                    'dashboard' => $libraryCount,
                    'daftar_buku' => 0,
                    'data_buku' => 0,
                    'peminjaman' => $libraryCount,
                    'peminjaman_saya' => $libraryCount,
                    'pengembalian' => $user->canManageLibraryModule() ? $libraryCount : 0,
                    'denda' => $libraryCount,
                ],
                'zi' => [
                    'rekapan_zi' => (int) ($moduleCounts['progress_zi'] ?? 0),
                    'monitoring' => (int) ($moduleCounts['progress_zi'] ?? 0),
                    'pedoman_zi' => 0,
                    'laporan' => 0,
                    'verifikasi' => $this->countIdPrefix($items, 'zi-approval-'),
                ],
            ],
        ];

        return $this->cache[$user->id] = $badges;
    }

    protected function countIdPrefix(Collection $items, $prefix)
    {
        return $items->filter(function ($item) use ($prefix) {
            return strpos((string) ($item['id'] ?? ''), $prefix) === 0;
        })->count();
    }

    protected function supplyCount(User $user)
    {
        if (!Schema::hasTable('supply_requests') || !$user->canAccessSupplyModule()) {
            return 0;
        }

        $query = SupplyRequest::where('status', SupplyRequest::STATUS_PENDING);

        if (!$user->canManageSupplyModule()) {
            $query->where('user_id', $user->id);
        }

        return $query->count();
    }

    protected function libraryCount(User $user)
    {
        if (!Schema::hasTable('library_loans') || !$user->canAccessLibraryModule()) {
            return 0;
        }

        $query = Loan::where('status', '!=', 'dikembalikan')
            ->whereDate('due_date', '<', now('Asia/Jayapura')->toDateString());

        if (!$user->canManageLibraryModule()) {
            $query->where('user_id', $user->id);
        }

        return $query->count();
    }
}
