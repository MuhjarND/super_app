<?php

namespace App\Services;

use App\Jabatan;
use App\Role;
use App\Unit;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class UserWorkbookSyncService
{
    public function sync($path, $resetPasswords = false)
    {
        $rows = $this->parseWorkbook($path);
        $unitMap = Unit::whereIn('kode', ['PIMPINAN', 'KESEKRETARIATAN', 'KEPANITERAAN'])->pluck('id', 'kode');
        $roleMap = Role::pluck('id', 'name');
        $summary = [
            'rows_read' => count($rows),
            'users_created' => 0,
            'users_updated' => 0,
            'users_skipped' => 0,
            'roles_synced' => 0,
        ];

        DB::transaction(function () use ($rows, $unitMap, $roleMap, $resetPasswords, &$summary) {
            foreach ($rows as $row) {
                $email = Str::lower(trim((string) ($row['email'] ?? '')));
                $name = trim((string) ($row['nama'] ?? ''));
                $jabatanLabel = trim((string) ($row['jabatan'] ?? ''));

                if ($email === '' || $name === '' || $jabatanLabel === '') {
                    $summary['users_skipped']++;
                    continue;
                }

                $jabatanKey = $this->normalizeJabatanKey($jabatanLabel);
                $resolvedJabatan = $this->resolveJabatan($jabatanKey);
                $unitId = $this->resolveUnitId($jabatanKey, $unitMap);
                $roleNames = $this->resolveRoleNames($jabatanKey);
                $roleIds = collect($roleNames)
                    ->map(function ($roleName) use ($roleMap) {
                        return $roleMap[$roleName] ?? null;
                    })
                    ->filter()
                    ->values()
                    ->all();

                $existingUser = User::where('email', $email)->first();
                $payload = [
                    'name' => $name,
                    'email' => $email,
                    'jabatan_id' => data_get($resolvedJabatan, 'id'),
                    'jabatan_keterangan' => $jabatanLabel,
                    'unit_id' => $unitId ?: optional($existingUser)->unit_id,
                    'bidang_id' => optional($existingUser)->bidang_id,
                    'hirarki' => $this->resolveHirarki($row['hirarki'] ?? null, optional($existingUser)->hirarki),
                    'nip' => optional($existingUser)->nip,
                    'no_hp' => $this->normalizePhone($row['nomor hp'] ?? null) ?: optional($existingUser)->no_hp,
                    'status_aktif_pegawai' => true,
                ];

                if (!$existingUser || $resetPasswords) {
                    $payload['password'] = Hash::make($this->resolvePassword($row['password'] ?? null));
                }

                $user = User::updateOrCreate(['email' => $email], $payload);
                $user->roles()->sync($roleIds);

                $summary[$existingUser ? 'users_updated' : 'users_created']++;
                $summary['roles_synced'] += count($roleIds);
            }
        });

        return $summary;
    }

    protected function parseWorkbook($path)
    {
        if (!is_file($path)) {
            throw new RuntimeException('File workbook user tidak ditemukan: ' . $path);
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Workbook user tidak bisa dibuka: ' . $path);
        }

        $sharedStrings = [];
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXml !== false) {
            $sharedStringsDoc = simplexml_load_string($sharedStringsXml);
            foreach ($sharedStringsDoc->si as $item) {
                if (isset($item->t)) {
                    $sharedStrings[] = (string) $item->t;
                    continue;
                }

                $text = '';
                foreach ($item->r as $run) {
                    $text .= (string) $run->t;
                }
                $sharedStrings[] = $text;
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false) {
            throw new RuntimeException('Sheet1 tidak ditemukan di workbook user.');
        }

        $sheet = simplexml_load_string($sheetXml);
        $headers = [];
        $rows = [];

        foreach ($sheet->sheetData->row as $row) {
            $rowNumber = (int) $row['r'];
            $cells = [];

            foreach ($row->c as $cell) {
                $reference = (string) $cell['r'];
                preg_match('/([A-Z]+)(\d+)/', $reference, $matches);
                $column = $matches[1] ?? null;
                if (!$column) {
                    continue;
                }

                $value = isset($cell->v) ? (string) $cell->v : '';
                $type = (string) $cell['t'];

                if ($type === 's') {
                    $value = $sharedStrings[(int) $value] ?? '';
                } elseif ($type === 'inlineStr') {
                    $value = (string) $cell->is->t;
                }

                $cells[$column] = trim($value);
            }

            if ($rowNumber === 1) {
                foreach ($cells as $column => $value) {
                    $headers[$column] = Str::lower(trim($value));
                }
                continue;
            }

            if (empty(array_filter($cells, function ($value) {
                return $value !== '';
            }))) {
                continue;
            }

            $mappedRow = [];
            foreach ($cells as $column => $value) {
                $header = $headers[$column] ?? null;
                if ($header) {
                    $mappedRow[$header] = $value;
                }
            }

            if (!empty($mappedRow)) {
                $rows[] = $mappedRow;
            }
        }

        return $rows;
    }

    protected function resolveJabatan($jabatanKey)
    {
        $kodeMap = [
            'ketua' => 'KPTA',
            'wakil_ketua' => 'WKPTA',
            'sekretaris' => 'SEK',
            'panitera' => 'PAN',
        ];

        $kode = $kodeMap[$jabatanKey] ?? null;

        return $kode ? Jabatan::where('kode', $kode)->first() : null;
    }

    protected function resolveUnitId($jabatanKey, $unitMap)
    {
        $unitCodeMap = [
            'ketua' => 'PIMPINAN',
            'wakil_ketua' => 'PIMPINAN',
            'hakim_tinggi' => 'PIMPINAN',
            'sekretaris' => 'KESEKRETARIATAN',
            'kepala_bagian' => 'KESEKRETARIATAN',
            'kepala_sub_bagian' => 'KESEKRETARIATAN',
            'staf_kesekretariatan' => 'KESEKRETARIATAN',
            'panitera' => 'KEPANITERAAN',
            'panitera_muda' => 'KEPANITERAAN',
            'panitera_pengganti' => 'KEPANITERAAN',
            'staf_kepaniteraan' => 'KEPANITERAAN',
        ];

        $unitCode = $unitCodeMap[$jabatanKey] ?? null;

        return $unitCode ? ($unitMap[$unitCode] ?? null) : null;
    }

    protected function resolveRoleNames($jabatanKey)
    {
        $map = [
            'ketua' => ['ketua', 'approval', 'peserta', 'atasan_langsung'],
            'wakil_ketua' => ['wakil_ketua', 'approval', 'peserta', 'atasan_langsung'],
            'sekretaris' => ['sekretaris', 'approval', 'peserta', 'atasan_langsung'],
            'panitera' => ['panitera', 'approval', 'peserta', 'atasan_langsung'],
            'kepala_bagian' => ['kabag', 'peserta', 'atasan_langsung'],
            'kepala_sub_bagian' => ['kasubag', 'peserta', 'atasan_langsung'],
            'panitera_muda' => ['panmud', 'peserta', 'atasan_langsung'],
            'hakim_tinggi' => ['peserta', 'atasan_langsung'],
            'panitera_pengganti' => ['pegawai', 'peserta'],
            'staf_kesekretariatan' => ['pegawai', 'peserta'],
            'staf_kepaniteraan' => ['pegawai', 'peserta'],
        ];

        return $map[$jabatanKey] ?? ['pegawai', 'peserta'];
    }

    protected function normalizeJabatanKey($jabatan)
    {
        $normalized = trim(preg_replace('/\s+/', ' ', Str::lower((string) $jabatan)));

        $map = [
            'ketua' => 'ketua',
            'wakil ketua' => 'wakil_ketua',
            'hakim tinggi' => 'hakim_tinggi',
            'sekretaris' => 'sekretaris',
            'panitera' => 'panitera',
            'kepala bagian' => 'kepala_bagian',
            'kepala sub bagian' => 'kepala_sub_bagian',
            'panitera muda' => 'panitera_muda',
            'panitera pengganti' => 'panitera_pengganti',
            'staf kesekretariatan' => 'staf_kesekretariatan',
            'staf kepaniteraan' => 'staf_kepaniteraan',
        ];

        return $map[$normalized] ?? 'pegawai';
    }

    protected function resolveHirarki($value, $fallback = 999)
    {
        $number = (int) preg_replace('/\D+/', '', (string) $value);

        return $number > 0 ? $number : ($fallback ?: 999);
    }

    protected function normalizePhone($value)
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        if ($digits === '') {
            return null;
        }

        if (Str::startsWith($digits, '62')) {
            return '0' . substr($digits, 2);
        }

        return Str::startsWith($digits, '0') ? $digits : '0' . $digits;
    }

    protected function resolvePassword($value)
    {
        $password = trim((string) $value);

        return $password !== '' ? $password : 'ptapabar';
    }
}
