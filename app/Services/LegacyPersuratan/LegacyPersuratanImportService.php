<?php

namespace App\Services\LegacyPersuratan;

use App\Disposisi;
use App\KategoriSurat;
use App\KlasifikasiKode;
use App\SuratKeluar;
use App\SuratMasuk;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LegacyPersuratanImportService
{
    protected $legacyConnection;
    protected $legacyPublicPath;
    protected $skipFiles;
    protected $summary = [];
    protected $fallbackUser;
    protected $legacyUserRows = [];
    protected $legacyUserMap = [];
    protected $klasifikasiMap = [];
    protected $fungsiMap = [];
    protected $kegiatanMap = [];
    protected $transaksiMap = [];
    protected $petunjukMap = [];
    protected $suratMasukMap = [];
    protected $legacyKlasifikasiRows = [];
    protected $fallbackKlasifikasiId;
    protected $fallbackKategoriSuratId;

    public function import($legacyConnection, $legacyPublicPath, $skipFiles = false)
    {
        $this->legacyConnection = $legacyConnection;
        $this->legacyPublicPath = rtrim($legacyPublicPath, '\\/');
        $this->skipFiles = (bool) $skipFiles;
        $this->summary = [
            'legacy_users_mapped' => 0,
            'legacy_users_unmatched' => 0,
            'kategori_surats_synced' => 0,
            'klasifikasi_synced' => 0,
            'fungsi_synced' => 0,
            'kegiatan_synced' => 0,
            'transaksi_synced' => 0,
            'surat_masuk_imported' => 0,
            'surat_masuk_updated' => 0,
            'surat_keluar_imported' => 0,
            'surat_keluar_updated' => 0,
            'disposisi_imported' => 0,
            'disposisi_updated' => 0,
            'file_copied' => 0,
            'file_missing' => 0,
            'recipients_unmatched' => 0,
            'creators_fallback' => 0,
        ];

        $legacyDb = DB::connection($legacyConnection);
        $this->ensureLegacyTables($legacyDb);

        $this->fallbackUser = $this->resolveFallbackUser();

        DB::transaction(function () use ($legacyDb) {
            $this->buildUserMap($legacyDb);
            $this->syncKlasifikasiHierarchy($legacyDb);
            $this->syncPetunjukMap($legacyDb);
            $this->importSuratMasuk($legacyDb);
            $this->importDisposisi($legacyDb);
            $this->importSuratKeluar($legacyDb);
            $this->refreshSuratMasukStatus();
        });

        return $this->summary;
    }

    protected function ensureLegacyTables($legacyDb)
    {
        foreach ([
            'users',
            'transaksi_surat_masuk',
            'detail_transaksi_surat_masuk',
            'transaksi_surat_keluar',
            'detail_transaksi_surat',
            'ref_klasifikasi',
            'ref_fungsi',
            'ref_kegiatan',
            'ref_transaksi',
        ] as $table) {
            if (!$legacyDb->getSchemaBuilder()->hasTable($table)) {
                throw new \RuntimeException('Tabel legacy tidak ditemukan: ' . $table);
            }
        }
    }

    protected function buildUserMap($legacyDb)
    {
        $legacyUsers = $legacyDb->table('users')
            ->leftJoin('daftar_pegawai', 'users.id', '=', 'daftar_pegawai.id_user')
            ->select(
                'users.id',
                'users.name',
                'users.email',
                'daftar_pegawai.nip',
                'daftar_pegawai.no_wa'
            )
            ->get();

        $currentUsers = User::query()
            ->select('id', 'name', 'email', 'nip', 'jabatan_id')
            ->get();

        $byEmail = [];
        $byNip = [];
        $byName = [];

        foreach ($currentUsers as $user) {
            if ($user->email) {
                $byEmail[Str::lower(trim($user->email))] = $user;
            }
            if ($user->nip) {
                $byNip[$this->digitsOnly($user->nip)] = $user;
            }

            $normalizedName = $this->normalizeName($user->name);
            if ($normalizedName !== '') {
                $byName[$normalizedName][] = $user;
            }
        }

        foreach ($legacyUsers as $legacyUser) {
            $currentUser = null;
            $emailKey = Str::lower(trim((string) $legacyUser->email));
            $nipKey = $this->digitsOnly($legacyUser->nip);
            $nameKey = $this->normalizeName($legacyUser->name);

            if ($emailKey !== '' && isset($byEmail[$emailKey])) {
                $currentUser = $byEmail[$emailKey];
            } elseif ($nipKey !== '' && isset($byNip[$nipKey])) {
                $currentUser = $byNip[$nipKey];
            } elseif ($nameKey !== '' && isset($byName[$nameKey]) && count($byName[$nameKey]) === 1) {
                $currentUser = $byName[$nameKey][0];
            }

            $this->legacyUserRows[$legacyUser->id] = $legacyUser;
            $this->legacyUserMap[$legacyUser->id] = $currentUser ? $currentUser->id : null;
        }

        $this->summary['legacy_users_mapped'] = count(array_filter($this->legacyUserMap));
        $this->summary['legacy_users_unmatched'] = count(array_filter($this->legacyUserMap, function ($value) {
            return $value === null;
        }));
    }

    protected function syncKlasifikasiHierarchy($legacyDb)
    {
        $legacyKlasifikasi = $legacyDb->table('ref_klasifikasi')
            ->orderBy('id')
            ->get();

        foreach ($legacyKlasifikasi as $row) {
            $klasifikasi = KlasifikasiKode::updateOrCreate(
                ['tipe' => 'klasifikasi', 'kode' => trim((string) $row->kode)],
                ['nama' => trim((string) $row->deskripsi), 'parent_id' => null]
            );

            $kategori = KategoriSurat::firstOrCreate(
                ['kode' => trim((string) $row->kode)],
                [
                    'nama' => trim((string) $row->deskripsi),
                    'keterangan' => 'Migrasi dari aplikasi persuratan lama',
                    'aktif' => true,
                ]
            );

            if ($kategori->wasRecentlyCreated) {
                $this->summary['kategori_surats_synced']++;
            }

            $this->klasifikasiMap[$row->id] = $klasifikasi->id;
            $this->legacyKlasifikasiRows[$row->id] = $row;
            $this->summary['klasifikasi_synced']++;
        }

        foreach ($legacyDb->table('ref_fungsi')->orderBy('id')->get() as $row) {
            $parentId = $this->klasifikasiMap[$row->id_ref_klasifikasi] ?? null;
            $fungsi = KlasifikasiKode::updateOrCreate(
                [
                    'tipe' => 'fungsi',
                    'parent_id' => $parentId,
                    'kode' => trim((string) $row->kode),
                ],
                ['nama' => trim((string) $row->deskripsi)]
            );

            $this->fungsiMap[$row->id] = $fungsi->id;
            $this->summary['fungsi_synced']++;
        }

        foreach ($legacyDb->table('ref_kegiatan')->orderBy('id')->get() as $row) {
            $parentId = $this->fungsiMap[$row->id_ref_fungsi] ?? null;
            $kegiatan = KlasifikasiKode::updateOrCreate(
                [
                    'tipe' => 'kegiatan',
                    'parent_id' => $parentId,
                    'kode' => trim((string) $row->kode),
                ],
                ['nama' => trim((string) $row->deskripsi)]
            );

            $this->kegiatanMap[$row->id] = $kegiatan->id;
            $this->summary['kegiatan_synced']++;
        }

        foreach ($legacyDb->table('ref_transaksi')->orderBy('id')->get() as $row) {
            $parentId = $this->kegiatanMap[$row->id_ref_kegiatan] ?? null;
            $transaksi = KlasifikasiKode::updateOrCreate(
                [
                    'tipe' => 'transaksi',
                    'parent_id' => $parentId,
                    'kode' => trim((string) $row->kode),
                ],
                ['nama' => trim((string) $row->deskripsi)]
            );

            $this->transaksiMap[$row->id] = $transaksi->id;
            $this->summary['transaksi_synced']++;
        }

        $fallbackKlasifikasi = KlasifikasiKode::firstOrCreate(
            ['tipe' => 'klasifikasi', 'kode' => 'LEG'],
            ['nama' => 'Klasifikasi Legacy Migrasi', 'parent_id' => null]
        );

        $fallbackKategori = KategoriSurat::firstOrCreate(
            ['kode' => 'LEG'],
            [
                'nama' => 'Kategori Legacy Migrasi',
                'keterangan' => 'Kategori fallback untuk data persuratan lama yang tidak memiliki klasifikasi valid.',
                'aktif' => true,
            ]
        );

        $this->fallbackKlasifikasiId = $fallbackKlasifikasi->id;
        $this->fallbackKategoriSuratId = $fallbackKategori->id;
    }

    protected function syncPetunjukMap($legacyDb)
    {
        if (!$legacyDb->getSchemaBuilder()->hasTable('ref_petunjuk_disposisi')) {
            return;
        }

        foreach ($legacyDb->table('ref_petunjuk_disposisi')->get() as $row) {
            $this->petunjukMap[$row->id] = trim((string) $row->name);
        }
    }

    protected function importSuratMasuk($legacyDb)
    {
        $hasDetailRows = $legacyDb->table('detail_transaksi_surat_masuk')
            ->select('id_surat', DB::raw('COUNT(*) AS jumlah'))
            ->groupBy('id_surat')
            ->pluck('jumlah', 'id_surat');

        foreach ($legacyDb->table('transaksi_surat_masuk')->orderBy('id')->get() as $row) {
            $suratMasuk = $this->resolveLegacySuratMasukModel($row);
            $isNew = !$suratMasuk->exists;

            $creatorId = $this->resolveUserId($row->created_by, true);
            $klasifikasiId = $this->klasifikasiMap[$row->klasifikasi_id] ?? null;
            $kategoriId = $this->resolveKategoriSuratId($row->klasifikasi_id);

            $suratMasuk->fill([
                'nomor_surat' => trim((string) $row->no_surat),
                'opsi_pengirim' => $this->mapLegacyPengirimOption($row->is_internal),
                'klasifikasi_kode_id' => $klasifikasiId,
                'kategori_surat_id' => $kategoriId,
                'pengirim' => trim((string) $row->pengirim),
                'perihal' => trim((string) $row->perihal),
                'tanggal_surat' => $row->tgl_surat,
                'sifat' => $this->mapLegacySifat($row),
                'file_path' => $this->copyLegacyFile('surat_masuk', $row->file, 'surat-masuk/legacy', $row->id, true),
                'status' => $this->mapLegacySuratMasukStatus($row->id_status, (int) ($hasDetailRows[$row->id] ?? 0)),
                'created_by' => $creatorId,
            ]);

            $suratMasuk->timestamps = false;
            $suratMasuk->created_at = $this->normalizeTimestamp($row->created_at);
            $suratMasuk->updated_at = $this->normalizeTimestamp($row->updated_at ?: $row->created_at);
            $suratMasuk->save();

            $this->suratMasukMap[$row->id] = $suratMasuk->id;
            $this->summary[$isNew ? 'surat_masuk_imported' : 'surat_masuk_updated']++;
        }
    }

    protected function importDisposisi($legacyDb)
    {
        $details = $legacyDb->table('detail_transaksi_surat_masuk')
            ->orderBy('created_at')
            ->get();

        $sequenceBySurat = [];

        foreach ($details as $detail) {
            $suratMasukId = $this->suratMasukMap[$detail->id_surat] ?? null;
            if (!$suratMasukId) {
                continue;
            }

            $legacySourceId = $this->buildDisposisiLegacySourceId($detail, $sequenceBySurat);
            $disposisi = Disposisi::firstOrNew(['legacy_source_id' => $legacySourceId]);
            $isNew = !$disposisi->exists;

            $dariUserId = $this->resolveUserId($detail->id_asal, false);
            $kepadaUserId = $this->resolveUserId($detail->id_penerima, false);
            $dariUser = $dariUserId ? User::find($dariUserId) : null;
            $kepadaUser = $kepadaUserId ? User::find($kepadaUserId) : null;

            $status = $this->mapLegacyDisposisiStatus($detail->status);

            $disposisi->fill([
                'surat_masuk_id' => $suratMasukId,
                'dari_user_id' => $dariUserId ?: $this->fallbackUser->id,
                'kepada_user_id' => $kepadaUserId ?: $this->fallbackUser->id,
                'dari_jabatan_id' => optional($dariUser)->jabatan_id,
                'kepada_jabatan_id' => optional($kepadaUser)->jabatan_id,
                'petunjuk' => $this->petunjukMap[$detail->petunjuk] ?? null,
                'catatan' => $detail->catatan,
                'catatan_tindak_lanjut' => null,
                'tipe' => ((int) $detail->status === 4) ? 'naikan' : 'disposisi',
                'status' => $status,
                'priority_level' => 'normal',
                'completed_at' => $status === 'ditindaklanjuti' ? $this->normalizeTimestamp($detail->created_at) : null,
            ]);

            $disposisi->timestamps = false;
            $disposisi->created_at = $this->normalizeTimestamp($detail->created_at);
            $disposisi->updated_at = $this->normalizeTimestamp($detail->created_at);
            $disposisi->save();

            $this->summary[$isNew ? 'disposisi_imported' : 'disposisi_updated']++;
        }
    }

    protected function importSuratKeluar($legacyDb)
    {
        $recipientMap = [];
        foreach ($legacyDb->table('detail_transaksi_surat')->get() as $detail) {
            $recipientMap[$detail->id_surat][] = $detail->id_penerima;
        }

        foreach ($legacyDb->table('transaksi_surat_keluar')->orderBy('id')->get() as $row) {
            $suratKeluar = $this->resolveLegacySuratKeluarModel($row);
            $isNew = !$suratKeluar->exists;
            $nomorSurat = $this->ensureUniqueSuratKeluarNomor(
                $this->normalizeLegacyNomorSurat($row->no_surat, $row->id),
                $row->id,
                $suratKeluar->exists ? $suratKeluar->id : null
            );

            $creatorId = $this->resolveUserId($row->created_by, true);
            $klasifikasiId = $this->klasifikasiMap[$row->id_ref_klasifikasi] ?? null;
            $kategoriId = $this->resolveKategoriSuratId($row->id_ref_klasifikasi);

            $suratKeluar->fill([
                'nomor_surat' => $nomorSurat,
                'nomor_urut' => (int) ($row->no_agenda ?: 0),
                'tahun_surat' => (int) ($row->tahun ?: date('Y')),
                'klasifikasi_kode_id' => $klasifikasiId ?: $this->fallbackKlasifikasiId,
                'kategori_surat_id' => $kategoriId ?: $this->fallbackKategoriSuratId,
                'kode_fungsi_id' => $this->fungsiMap[$row->id_ref_fungsi] ?? null,
                'kode_kegiatan_id' => $this->kegiatanMap[$row->id_ref_kegiatan] ?? null,
                'kode_transaksi_id' => $this->transaksiMap[$row->id_ref_transaksi] ?? null,
                'nomenklatur_jabatan' => $this->mapLegacyNomenklatur($row->id_nomenklatur_jabatan),
                'opsi_penerima' => ((int) $row->internal === 1) ? 'internal' : 'external',
                'penerima_external' => ((int) $row->internal === 1) ? null : $this->normalizeLegacyText($row->tujuan),
                'perihal' => $this->normalizeLegacyText($row->perihal, 'Surat keluar legacy #' . $row->id),
                'tanggal_surat' => $this->normalizeLegacyDate($row->tgl_surat, $row->tahun),
                'has_lampiran' => !empty($row->file),
                'file_path' => $this->copyLegacyFile('surat_keluar', $row->file, 'surat-keluar/legacy', $row->id),
                'status' => ((int) $row->id_status === 2) ? 'lengkap' : 'draft',
                'created_by' => $creatorId,
            ]);

            $suratKeluar->timestamps = false;
            $suratKeluar->created_at = $this->normalizeTimestamp($row->created_at);
            $suratKeluar->updated_at = $this->normalizeTimestamp($row->updated_at ?: $row->created_at);
            $suratKeluar->save();

            $mappedRecipients = [];
            foreach ($recipientMap[$row->id] ?? [] as $legacyRecipientId) {
                $mappedUserId = $this->resolveUserId($legacyRecipientId, false);
                if ($mappedUserId) {
                    $mappedRecipients[] = $mappedUserId;
                } else {
                    $this->summary['recipients_unmatched']++;
                }
            }

            if ($suratKeluar->opsi_penerima === 'internal') {
                $suratKeluar->penerimaInternal()->sync(array_values(array_unique($mappedRecipients)));
            } else {
                $suratKeluar->penerimaInternal()->detach();
            }

            $this->summary[$isNew ? 'surat_keluar_imported' : 'surat_keluar_updated']++;
        }
    }

    protected function refreshSuratMasukStatus()
    {
        SuratMasuk::whereNotNull('legacy_source_id')->chunkById(100, function ($items) {
            foreach ($items as $suratMasuk) {
                $status = 'baru';

                if ($suratMasuk->disposisis()->exists()) {
                    $status = 'didisposisi';
                }

                if ($suratMasuk->disposisis()->where('status', 'ditindaklanjuti')->exists()) {
                    $status = 'selesai';
                }

                if ($suratMasuk->status !== $status) {
                    $suratMasuk->timestamps = false;
                    $suratMasuk->status = $status;
                    $suratMasuk->save();
                }
            }
        });
    }

    protected function resolveFallbackUser()
    {
        return User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['admin_surat', 'super_admin']);
        })->orderBy('id')->first() ?: User::orderBy('id')->firstOrFail();
    }

    protected function resolveLegacySuratMasukModel($row)
    {
        $model = SuratMasuk::where('legacy_source_id', $row->id)->first();

        if ($model) {
            return $model;
        }

        $model = SuratMasuk::where('nomor_surat', trim((string) $row->no_surat))
            ->whereDate('tanggal_surat', $row->tgl_surat)
            ->where('pengirim', trim((string) $row->pengirim))
            ->first();

        if ($model && !$model->legacy_source_id) {
            $model->legacy_source_id = $row->id;
            return $model;
        }

        return new SuratMasuk(['legacy_source_id' => $row->id]);
    }

    protected function resolveLegacySuratKeluarModel($row)
    {
        $model = SuratKeluar::where('legacy_source_id', $row->id)->first();

        if ($model) {
            return $model;
        }

        $model = SuratKeluar::where('nomor_surat', $this->normalizeLegacyNomorSurat($row->no_surat, $row->id))->first();

        if ($model && !$model->legacy_source_id) {
            $model->legacy_source_id = $row->id;
            return $model;
        }

        return new SuratKeluar(['legacy_source_id' => $row->id]);
    }

    protected function resolveUserId($legacyUserId, $allowFallback)
    {
        $resolved = $this->legacyUserMap[$legacyUserId] ?? null;

        if ($resolved) {
            return $resolved;
        }

        if ($allowFallback) {
            $this->summary['creators_fallback']++;
            return $this->fallbackUser->id;
        }

        return null;
    }

    protected function resolveKategoriSuratId($legacyKlasifikasiId)
    {
        $legacyKlasifikasi = $this->legacyKlasifikasiRows[$legacyKlasifikasiId] ?? null;
        if (!$legacyKlasifikasi) {
            return null;
        }

        return optional(
            KategoriSurat::whereRaw('UPPER(kode) = ?', [Str::upper(trim((string) $legacyKlasifikasi->kode))])->first()
        )->id;
    }

    protected function mapLegacyPengirimOption($isInternal)
    {
        return ((int) $isInternal === 1) ? 'mahkamah_agung' : 'non_mahkamah_agung';
    }

    protected function mapLegacySifat($row)
    {
        if ((int) ($row->kerahasiaan ?? 0) >= 2) {
            return 'sangat_rahasia';
        }

        if ((int) ($row->kerahasiaan ?? 0) === 1 || ($row->rahasia ?? 'false') === 'true') {
            return 'rahasia';
        }

        return 'biasa';
    }

    protected function mapLegacySuratMasukStatus($legacyStatus, $detailCount)
    {
        if ((int) $legacyStatus === 3) {
            return 'selesai';
        }

        if ($detailCount > 0 || in_array((int) $legacyStatus, [1, 2, 4, 5], true)) {
            return 'didisposisi';
        }

        return 'baru';
    }

    protected function mapLegacyDisposisiStatus($legacyStatus)
    {
        return ((int) $legacyStatus === 3) ? 'ditindaklanjuti' : 'pending';
    }

    protected function mapLegacyNomenklatur($legacyId)
    {
        $map = [
            1 => 'ketua',
            2 => 'panitera',
            3 => 'sekretaris',
            4 => 'wakil_ketua',
        ];

        return $map[(int) $legacyId] ?? 'sekretaris';
    }

    protected function copyLegacyFile($legacyFolder, $filename, $targetDir, $legacyId, $required = false)
    {
        if ($this->skipFiles || !$filename) {
            return $required ? $this->ensureMissingFilePlaceholder($targetDir, $legacyId, $legacyFolder, $filename) : null;
        }

        $source = $this->legacyPublicPath . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $legacyFolder . DIRECTORY_SEPARATOR . basename($filename);

        if (!is_file($source)) {
            $this->summary['file_missing']++;
            return $required ? $this->ensureMissingFilePlaceholder($targetDir, $legacyId, $legacyFolder, $filename) : null;
        }

        $targetPath = trim($targetDir, '/') . '/legacy-' . $legacyId . '-' . basename($filename);

        if (!Storage::disk('public')->exists($targetPath)) {
            Storage::disk('public')->makeDirectory(dirname($targetPath));
            copy($source, Storage::disk('public')->path($targetPath));
            $this->summary['file_copied']++;
        }

        return $targetPath;
    }

    protected function ensureMissingFilePlaceholder($targetDir, $legacyId, $legacyFolder, $filename)
    {
        $targetPath = trim($targetDir, '/') . '/legacy-missing-' . $legacyId . '.txt';

        if (!Storage::disk('public')->exists($targetPath)) {
            $content = implode(PHP_EOL, [
                'Dokumen legacy tidak ditemukan saat proses migrasi.',
                'Modul: ' . $legacyFolder,
                'Legacy ID: ' . $legacyId,
                'Nama file lama: ' . ($filename ?: '-'),
            ]);

            Storage::disk('public')->put($targetPath, $content);
            $this->summary['file_copied']++;
        }

        return $targetPath;
    }

    protected function buildDisposisiLegacySourceId($detail, array &$sequenceBySurat)
    {
        $base = ((int) $detail->id_surat * 100000) + ((int) $detail->id_asal * 100) + ((int) $detail->id_penerima);

        if (!isset($sequenceBySurat[$detail->id_surat])) {
            $sequenceBySurat[$detail->id_surat] = 0;
        }

        $sequenceBySurat[$detail->id_surat]++;

        return $base + $sequenceBySurat[$detail->id_surat];
    }

    protected function normalizeTimestamp($value)
    {
        if (!$value) {
            return now();
        }

        return Carbon::parse($value);
    }

    protected function normalizeDateFromYear($year)
    {
        $year = (int) $year;
        if ($year < 2000) {
            $year = (int) date('Y');
        }

        return $year . '-01-01';
    }

    protected function normalizeLegacyDate($dateValue, $fallbackYear)
    {
        $value = trim((string) $dateValue);

        if ($value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00' || strpos($value, '-0001') === 0) {
            return $this->normalizeDateFromYear($fallbackYear);
        }

        try {
            $date = Carbon::parse($value);

            if ((int) $date->format('Y') < 1900) {
                return $this->normalizeDateFromYear($fallbackYear);
            }

            return $date->format('Y-m-d');
        } catch (\Exception $exception) {
            return $this->normalizeDateFromYear($fallbackYear);
        }
    }

    protected function normalizeLegacyNomorSurat($nomorSurat, $legacyId)
    {
        $value = trim((string) $nomorSurat);

        if ($value === '' || $value === '0' || $value === '-') {
            return 'LEGACY-SK-' . $legacyId;
        }

        return $value;
    }

    protected function normalizeLegacyText($value, $fallback = null)
    {
        $text = trim((string) $value);

        if ($text === '' || $text === '0' || $text === '-') {
            return $fallback;
        }

        return $text;
    }

    protected function ensureUniqueSuratKeluarNomor($nomorSurat, $legacyId, $currentId = null)
    {
        $existing = SuratKeluar::where('nomor_surat', $nomorSurat)
            ->when($currentId, function ($query) use ($currentId) {
                $query->where('id', '!=', $currentId);
            })
            ->first();

        if (!$existing) {
            return $nomorSurat;
        }

        return $nomorSurat . '/L' . $legacyId;
    }

    protected function digitsOnly($value)
    {
        return preg_replace('/\D+/', '', (string) $value);
    }

    protected function normalizeName($value)
    {
        return trim(preg_replace('/\s+/', ' ', Str::lower((string) $value)));
    }
}
