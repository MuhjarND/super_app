<?php

namespace App\Services\LegacyPersuratan;

use App\Disposisi;
use App\DisposisiDokumentasi;
use App\SuratKeluar;
use App\SuratMasuk;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LegacyPersuratanRemoteFileSyncService
{
    protected $client;
    protected $sourceUrl;
    protected $maxBytes;
    protected $attempts;
    protected $summary = [];

    protected const ALLOWED_EXTENSIONS = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'jpg', 'jpeg', 'png', 'webp', 'txt', 'csv', 'zip', 'rar',
    ];

    public function sync($legacyConnection)
    {
        $this->sourceUrl = rtrim((string) config('legacy_persuratan.source_url'), '/');
        $this->maxBytes = (int) config('legacy_persuratan.remote_file_max_bytes', 26214400);
        $this->attempts = max(1, (int) config('legacy_persuratan.remote_file_attempts', 2));
        $this->summary = [
            'remote_file_downloaded' => 0,
            'remote_file_existing' => 0,
            'remote_file_failed' => 0,
            'remote_file_unmatched' => 0,
            'remote_surat_masuk_downloaded' => 0,
            'remote_surat_keluar_downloaded' => 0,
            'remote_tindak_lanjut_downloaded' => 0,
        ];

        $this->assertValidSourceUrl();
        $this->client = new Client([
            'connect_timeout' => (int) config('legacy_persuratan.remote_file_connect_timeout', 10),
            'timeout' => (int) config('legacy_persuratan.remote_file_timeout', 90),
            'verify' => true,
            'headers' => [
                'User-Agent' => 'SIMANTAP-LegacyFileSync/1.0',
                'Accept' => 'application/pdf,application/octet-stream,image/*,*/*;q=0.8',
            ],
        ]);

        $legacyDb = DB::connection($legacyConnection);
        $this->syncSuratMasuk($legacyDb);
        $this->syncSuratKeluar($legacyDb);
        $this->syncTindakLanjut($legacyDb);

        return $this->summary;
    }

    protected function syncSuratMasuk($legacyDb)
    {
        $legacyDb->table('transaksi_surat_masuk')
            ->select('id', 'file')
            ->whereNotNull('file')
            ->where('file', '!=', '')
            ->whereNotIn('file', ['0', '-'])
            ->orderBy('id')
            ->chunkById(50, function ($rows) {
                $models = SuratMasuk::whereIn('legacy_source_id', $rows->pluck('id'))
                    ->get()
                    ->keyBy('legacy_source_id');

                foreach ($rows as $row) {
                    $model = $models->get($row->id);
                    if (!$model) {
                        $this->summary['remote_file_unmatched']++;
                        continue;
                    }

                    $targetPath = $this->targetPath('surat-masuk/legacy', $row->id, $row->file);
                    $result = $this->downloadIfMissing('surat_masuk', $row->file, 'public', $targetPath);
                    if (!$result) {
                        continue;
                    }

                    if ($model->file_path !== $targetPath) {
                        $model->forceFill(['file_path' => $targetPath])->save();
                    }

                    if ($result['downloaded']) {
                        $this->summary['remote_surat_masuk_downloaded']++;
                    }
                }
            }, 'id');
    }

    protected function syncSuratKeluar($legacyDb)
    {
        $legacyDb->table('transaksi_surat_keluar')
            ->select('id', 'file')
            ->whereNotNull('file')
            ->where('file', '!=', '')
            ->whereNotIn('file', ['0', '-'])
            ->orderBy('id')
            ->chunkById(50, function ($rows) {
                $models = SuratKeluar::whereIn('legacy_source_id', $rows->pluck('id'))
                    ->get()
                    ->keyBy('legacy_source_id');

                foreach ($rows as $row) {
                    $model = $models->get($row->id);
                    if (!$model) {
                        $this->summary['remote_file_unmatched']++;
                        continue;
                    }

                    $targetPath = $this->targetPath('surat-keluar/legacy', $row->id, $row->file);
                    $result = $this->downloadIfMissing('surat_keluar', $row->file, 'public', $targetPath);
                    if (!$result) {
                        continue;
                    }

                    $changes = ['file_path' => $targetPath];
                    if ($model->status === 'draft') {
                        $changes['status'] = 'lengkap';
                    }
                    $model->forceFill($changes)->save();

                    if ($result['downloaded']) {
                        $this->summary['remote_surat_keluar_downloaded']++;
                    }
                }
            }, 'id');
    }

    protected function syncTindakLanjut($legacyDb)
    {
        $legacyDb->table('transaksi_surat_masuk')
            ->select('id', 'file_tindak_lanjut')
            ->whereNotNull('file_tindak_lanjut')
            ->where('file_tindak_lanjut', '!=', '')
            ->whereNotIn('file_tindak_lanjut', ['0', '-'])
            ->orderBy('id')
            ->chunkById(50, function ($rows) {
                $legacyIds = $rows->pluck('id')->map(function ($id) {
                    return $this->syntheticTindakLanjutId($id);
                });
                $disposisis = Disposisi::whereIn('legacy_source_id', $legacyIds)
                    ->get()
                    ->keyBy('legacy_source_id');

                foreach ($rows as $row) {
                    $disposisi = $disposisis->get($this->syntheticTindakLanjutId($row->id));
                    if (!$disposisi) {
                        $this->summary['remote_file_unmatched']++;
                        continue;
                    }

                    $targetPath = $this->targetPath(
                        'surat-masuk/tindak-lanjut/legacy',
                        $row->id,
                        $row->file_tindak_lanjut
                    );
                    $result = $this->downloadIfMissing(
                        'tindak_lanjut',
                        $row->file_tindak_lanjut,
                        'local',
                        $targetPath
                    );
                    if (!$result) {
                        continue;
                    }

                    DisposisiDokumentasi::updateOrCreate(
                        [
                            'disposisi_id' => $disposisi->id,
                            'file_path' => $targetPath,
                        ],
                        [
                            'uploaded_by' => null,
                            'original_name' => basename((string) $row->file_tindak_lanjut),
                            'mime_type' => $result['mime_type'],
                            'file_size' => $result['file_size'],
                        ]
                    );

                    if ($result['downloaded']) {
                        $this->summary['remote_tindak_lanjut_downloaded']++;
                    }
                }
            }, 'id');
    }

    protected function downloadIfMissing($legacyFolder, $filename, $disk, $targetPath)
    {
        if (Storage::disk($disk)->exists($targetPath) && Storage::disk($disk)->size($targetPath) > 0) {
            $this->summary['remote_file_existing']++;

            return $this->fileResult($disk, $targetPath, false);
        }

        Storage::disk($disk)->delete($targetPath);

        $basename = basename((string) $filename);
        $extension = strtolower(pathinfo($basename, PATHINFO_EXTENSION));
        if ($basename === '' || !in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            $this->recordFailure($legacyFolder, $basename, 'Ekstensi file tidak diizinkan.');
            return null;
        }

        $url = $this->sourceUrl . '/uploads/' . rawurlencode($legacyFolder) . '/' . rawurlencode($basename);
        Storage::disk($disk)->makeDirectory(dirname($targetPath));
        $temporaryPath = Storage::disk($disk)->path($targetPath . '.part-' . Str::random(8));

        for ($attempt = 1; $attempt <= $this->attempts; $attempt++) {
            try {
                $response = $this->client->request('GET', $url, [
                    'sink' => $temporaryPath,
                    'http_errors' => false,
                    'allow_redirects' => [
                        'max' => 3,
                        'strict' => true,
                        'referer' => false,
                        'protocols' => ['https', 'http'],
                    ],
                    'on_headers' => function ($response) {
                        $contentLength = (int) $response->getHeaderLine('Content-Length');
                        if ($contentLength > 0 && $contentLength > $this->maxBytes) {
                            throw new \RuntimeException('Ukuran file melebihi batas sinkronisasi.');
                        }
                    },
                ]);

                $status = (int) $response->getStatusCode();
                $contentType = strtolower(trim(explode(';', $response->getHeaderLine('Content-Type'))[0]));
                $size = is_file($temporaryPath) ? (int) filesize($temporaryPath) : 0;

                if ($status !== 200 || $size < 1 || $size > $this->maxBytes || $contentType === 'text/html') {
                    throw new \RuntimeException('Respons file dari SIMISOL tidak valid.');
                }

                $targetAbsolutePath = Storage::disk($disk)->path($targetPath);
                if (!@rename($temporaryPath, $targetAbsolutePath)) {
                    throw new \RuntimeException('File hasil unduhan tidak dapat disimpan.');
                }

                $this->summary['remote_file_downloaded']++;

                return $this->fileResult($disk, $targetPath, true, $contentType);
            } catch (\Throwable $exception) {
                if (is_file($temporaryPath)) {
                    @unlink($temporaryPath);
                }

                if ($attempt === $this->attempts) {
                    $this->recordFailure($legacyFolder, $basename, $exception->getMessage());
                    return null;
                }
            }
        }

        return null;
    }

    protected function fileResult($disk, $targetPath, $downloaded, $responseMime = null)
    {
        $absolutePath = Storage::disk($disk)->path($targetPath);
        $mime = $responseMime ?: (function_exists('mime_content_type') ? @mime_content_type($absolutePath) : null);

        return [
            'downloaded' => (bool) $downloaded,
            'file_size' => is_file($absolutePath) ? (int) filesize($absolutePath) : 0,
            'mime_type' => $mime ?: 'application/octet-stream',
        ];
    }

    protected function targetPath($directory, $legacyId, $filename)
    {
        return trim($directory, '/') . '/legacy-' . (int) $legacyId . '-' . basename((string) $filename);
    }

    protected function syntheticTindakLanjutId($legacySuratMasukId)
    {
        return 900000000000 + (int) $legacySuratMasukId;
    }

    protected function assertValidSourceUrl()
    {
        $parts = parse_url($this->sourceUrl);
        if (!$parts || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true) || empty($parts['host'])) {
            throw new \RuntimeException('URL sumber berkas SIMISOL tidak valid.');
        }
    }

    protected function recordFailure($folder, $filename, $message)
    {
        $this->summary['remote_file_failed']++;

        Log::warning('Berkas legacy SIMISOL gagal disinkronkan.', [
            'folder' => $folder,
            'filename' => $filename,
            'message' => $message,
        ]);
    }
}
