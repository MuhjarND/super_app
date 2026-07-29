<?php

namespace App\Services;

use App\KategoriRapat;
use App\Rapat;
use App\SuratKeluar;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DirectAttendanceService
{
    public function createFromSuratKeluar(SuratKeluar $suratKeluar, User $creator, array $data)
    {
        return DB::transaction(function () use ($suratKeluar, $creator, $data) {
            $suratKeluar = SuratKeluar::query()
                ->with('penerimaInternal')
                ->lockForUpdate()
                ->findOrFail($suratKeluar->id);

            $existing = Rapat::query()
                ->where('attendance_surat_keluar_id', $suratKeluar->id)
                ->first();

            if ($existing) {
                throw ValidationException::withMessages([
                    'surat_keluar_id' => 'Surat Keluar tersebut sudah digunakan sebagai dasar absensi.',
                ]);
            }

            $rapat = Rapat::create([
                'nomor_undangan' => $suratKeluar->nomor_surat_formatted,
                'judul' => trim((string) ($data['judul'] ?? ''))
                    ?: ($suratKeluar->perihal ?: 'Absensi Surat Keluar'),
                'deskripsi' => 'Absensi dibuat langsung berdasarkan Surat Keluar.',
                'kategori_rapat_id' => $this->resolveKategoriRapatId(),
                'kategori_surat_kode_id' => $suratKeluar->klasifikasi_kode_id,
                'nomenklatur_jabatan' => $suratKeluar->nomenklatur_jabatan,
                'tanggal' => $data['tanggal'],
                'waktu_mulai' => $data['waktu_mulai'],
                'tempat' => $data['tempat'],
                'bersama_satker' => $suratKeluar->opsi_penerima === 'external',
                'status' => 'disetujui',
                'token_qr' => (string) Str::uuid(),
                'public_code' => $this->newPublicCode(),
                'is_attendance_only' => true,
                'attendance_surat_keluar_id' => $suratKeluar->id,
                'created_by' => $creator->id,
            ]);

            $participants = $suratKeluar->penerimaInternal
                ->pluck('id')
                ->unique()
                ->values();
            $syncData = [];
            foreach ($participants as $index => $participantId) {
                $syncData[(int) $participantId] = ['urutan' => $index + 1];
            }
            $rapat->pesertas()->sync($syncData);

            return $rapat;
        });
    }

    protected function resolveKategoriRapatId()
    {
        $category = KategoriRapat::firstOrCreate(
            ['kode' => 'ABSENSI-SURAT'],
            [
                'nama' => 'Absensi Surat Keluar',
                'keterangan' => 'Kategori sistem untuk absensi yang dibuat langsung dari Surat Keluar.',
                'butuh_pakaian' => false,
                'aktif' => true,
            ]
        );

        return $category->id;
    }

    protected function newPublicCode()
    {
        do {
            $code = strtoupper(Str::random(12));
        } while (Rapat::where('public_code', $code)->exists());

        return $code;
    }
}
