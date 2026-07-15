<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityAuditService;
use App\Services\LegacyPersuratan\LegacyPersuratanSyncService;
use Illuminate\Support\Facades\Log;

class LegacyPersuratanSyncController extends Controller
{
    public function store(
        LegacyPersuratanSyncService $syncService,
        ActivityAuditService $auditService
    ) {
        $user = auth()->user();
        abort_unless($user && $user->hasRole('super_admin'), 403);

        $lockHandle = fopen(storage_path('app/legacy-persuratan-sync.lock'), 'c');
        $lockAcquired = $lockHandle && flock($lockHandle, LOCK_EX | LOCK_NB);

        if (!$lockAcquired) {
            if (is_resource($lockHandle)) {
                fclose($lockHandle);
            }

            return response()->json([
                'success' => false,
                'message' => 'Sinkronisasi SIMISOL sedang berjalan. Silakan tunggu hingga proses sebelumnya selesai.',
            ], 409);
        }

        @set_time_limit(0);

        try {
            $result = $syncService->sync();
            $summary = $result['summary'];

            $auditService->log('persuratan', 'legacy_persuratan_synced', null, [
                'subject_type' => 'legacy_persuratan',
                'subject_title' => 'Sinkronisasi data SIMISOL',
                'new_values_json' => $summary,
                'metadata_json' => [
                    'source_url' => $result['source_url'],
                    'synced_at' => $result['synced_at']->toDateTimeString(),
                ],
                'note' => 'Sinkronisasi manual dijalankan oleh Super Admin.',
            ], $user);

            $suratMasukBaru = (int) ($summary['surat_masuk_imported'] ?? 0);
            $suratMasukDiperbarui = (int) ($summary['surat_masuk_updated'] ?? 0);
            $suratKeluarBaru = (int) ($summary['surat_keluar_imported'] ?? 0);
            $suratKeluarDiperbarui = (int) ($summary['surat_keluar_updated'] ?? 0);
            $berkasDiunduh = (int) ($summary['remote_file_downloaded'] ?? 0);
            $berkasGagal = (int) ($summary['remote_file_failed'] ?? 0);

            return response()->json([
                'success' => true,
                'message' => sprintf(
                    'Sinkronisasi selesai. Surat masuk: %d baru, %d diperbarui. Surat keluar: %d baru, %d diperbarui. Berkas: %d diunduh, %d gagal.',
                    $suratMasukBaru,
                    $suratMasukDiperbarui,
                    $suratKeluarBaru,
                    $suratKeluarDiperbarui,
                    $berkasDiunduh,
                    $berkasGagal
                ),
                'summary' => $summary,
                'synced_at' => $result['synced_at']->translatedFormat('d F Y H:i'),
            ]);
        } catch (\Throwable $exception) {
            Log::error('Sinkronisasi persuratan SIMISOL gagal.', [
                'user_id' => $user->id,
                'message' => $exception->getMessage(),
                'exception' => get_class($exception),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Sinkronisasi gagal. Pastikan database dan folder aplikasi SIMISOL dapat diakses.',
            ], 500);
        } finally {
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
        }
    }
}
