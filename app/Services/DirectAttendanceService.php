<?php

namespace App\Services;

use App\KategoriRapat;
use App\PdfVerification;
use App\Rapat;
use App\SuratKeluar;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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

    public function deleteDirectAttendance(Rapat $rapat)
    {
        if (!$rapat->is_attendance_only) {
            throw ValidationException::withMessages([
                'rapat' => 'Hanya absensi yang dibuat langsung yang dapat dihapus dari halaman ini.',
            ]);
        }

        $signaturePaths = $rapat->attendances()
            ->whereNotNull('signature_path')
            ->pluck('signature_path')
            ->filter()
            ->unique()
            ->values();

        $hasPdfVerifications = Schema::hasTable('pdf_verifications');
        $verificationPaths = $hasPdfVerifications
            ? PdfVerification::query()
                ->where('module', 'rapat')
                ->where('document_type', 'laporan_absensi')
                ->where('document_id', (string) $rapat->id)
                ->whereNotNull('file_path')
                ->pluck('file_path')
                ->filter()
                ->values()
            : collect();

        DB::transaction(function () use ($rapat, $hasPdfVerifications) {
            $rapat->pesertas()->detach();
            $rapat->attendances()->delete();
            if ($hasPdfVerifications) {
                PdfVerification::query()
                    ->where('module', 'rapat')
                    ->where('document_type', 'laporan_absensi')
                    ->where('document_id', (string) $rapat->id)
                    ->delete();
            }
            $rapat->delete();
        });

        $storedPaths = $signaturePaths
            ->concat($verificationPaths)
            ->filter()
            ->unique()
            ->values();
        if ($storedPaths->isNotEmpty()) {
            Storage::disk('public')->delete($storedPaths->all());
        }
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
