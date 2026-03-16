<?php

namespace App\Services;

use App\Rapat;
use App\RapatLaporan;
use Illuminate\Support\Collection;

class RapatLaporanService
{
    public function ensureForRapats($rapats, $userId = null)
    {
        $rapats = $rapats instanceof Collection ? $rapats : collect($rapats);

        foreach ($rapats as $rapat) {
            foreach (['gabungan', 'tindak_lanjut'] as $jenis) {
                $defaults = $this->defaultsFor($rapat, $jenis, $userId);
                $laporan = RapatLaporan::firstOrCreate(
                    ['rapat_id' => $rapat->id, 'jenis' => $jenis],
                    $defaults
                );

                $laporan->fill([
                    'judul' => $defaults['judul'],
                    'deskripsi' => $defaults['deskripsi'],
                    'is_ready' => $defaults['is_ready'],
                    'updated_by' => $userId,
                ]);

                if ($laporan->status !== 'arsip') {
                    $laporan->status = 'aktif';
                }

                $laporan->save();
            }
        }
    }

    public function defaultsFor(Rapat $rapat, $jenis, $userId = null)
    {
        $notulensi = $rapat->notulensi;
        $hasFinalNotulensi = $notulensi && !$notulensi->tidak_membuat_notulen;

        return [
            'judul' => $this->buildTitle($rapat, $jenis),
            'deskripsi' => $jenis === 'gabungan'
                ? 'Laporan gabungan undangan, absensi, dan notulensi rapat.'
                : 'Laporan tindak lanjut berdasarkan hasil rapat dan rekomendasi notulensi.',
            'status' => 'aktif',
            'is_ready' => $jenis === 'gabungan'
                ? (bool) $hasFinalNotulensi
                : (bool) ($notulensi && ($notulensi->rekomendasi || $notulensi->hasil_rapat || $notulensi->catatan)),
            'created_by' => $userId,
            'updated_by' => $userId,
        ];
    }

    public function buildTitle(Rapat $rapat, $jenis)
    {
        return ($jenis === 'gabungan' ? 'Laporan Gabungan' : 'Laporan Tindak Lanjut') . ' - ' . $rapat->judul;
    }
}
