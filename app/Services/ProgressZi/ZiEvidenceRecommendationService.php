<?php

namespace App\Services\ProgressZi;

use App\LeaveRequest;
use App\Rapat;
use App\RapatLaporan;
use App\RapatNotulensi;
use App\SuratKeluar;
use App\SuratMasuk;
use App\User;
use App\ZiActivity;
use App\ZiArea;
use App\ZiGuidelineSubPoint;
use Illuminate\Support\Collection;

class ZiEvidenceRecommendationService
{
    protected $areaKeywordMap = [
        'manajemen_perubahan' => ['zi', 'integritas', 'komitmen', 'perubahan', 'budaya', 'agen', 'tim'],
        'tatalaksana' => ['sop', 'prosedur', 'tatalaksana', 'digital', 'aplikasi', 'surat', 'arsip'],
        'sdm' => ['sdm', 'pegawai', 'kepegawaian', 'cuti', 'diklat', 'kinerja', 'disiplin'],
        'akuntabilitas' => ['renstra', 'perjanjian', 'kinerja', 'monitoring', 'evaluasi', 'laporan'],
        'pengawasan' => ['pengawasan', 'gratifikasi', 'pengendalian', 'pengaduan', 'whistleblowing'],
        'pelayanan_publik' => ['layanan', 'pelayanan', 'maklumat', 'survei', 'kepuasan', 'ptsp'],
    ];

    public function recommendForActivity(ZiActivity $activity, User $user, $limit = 8)
    {
        $keywords = $this->buildKeywords($activity);
        if (empty($keywords)) {
            return collect();
        }

        return collect()
            ->merge($this->recommendSuratKeluar($keywords, $user))
            ->merge($this->recommendSuratMasuk($keywords, $user))
            ->merge($this->recommendRapat($keywords, $user))
            ->merge($this->recommendNotulensi($keywords, $user))
            ->merge($this->recommendLaporan($keywords, $user))
            ->merge($this->recommendCuti($keywords, $user))
            ->unique(function ($item) {
                return $item['linked_source'];
            })
            ->sortByDesc('score')
            ->take($limit)
            ->values();
    }

    public function recommendForSubPoint(ZiArea $area, ZiGuidelineSubPoint $subPoint, User $user, $limit = 6)
    {
        $keywords = $this->buildSubPointKeywords($area, $subPoint);
        if (empty($keywords)) {
            return collect();
        }

        return collect()
            ->merge($this->recommendSuratKeluar($keywords, $user))
            ->merge($this->recommendSuratMasuk($keywords, $user))
            ->merge($this->recommendRapat($keywords, $user))
            ->merge($this->recommendNotulensi($keywords, $user))
            ->merge($this->recommendLaporan($keywords, $user))
            ->merge($this->recommendCuti($keywords, $user))
            ->unique(function ($item) {
                return $item['linked_source'];
            })
            ->sortByDesc('score')
            ->take($limit)
            ->values();
    }

    protected function buildKeywords(ZiActivity $activity)
    {
        $parts = collect([
            $activity->name,
            $activity->description,
            optional($activity->area)->name,
            optional($activity->area)->description,
        ])->merge($activity->indicators->pluck('name'))->merge($activity->indicators->pluck('description'))->filter();

        return $parts->flatMap(function ($text) {
            return preg_split('/[^\pL\pN]+/u', mb_strtolower((string) $text));
        })->map(function ($token) {
            return trim($token);
        })->filter(function ($token) {
            return mb_strlen($token) >= 4 && !in_array($token, ['yang', 'dengan', 'untuk', 'pada', 'atau', 'dari', 'area', 'indikator', 'kegiatan'], true);
        })->merge($this->buildAreaKeywords($activity))->unique()->values()->all();
    }

    protected function buildSubPointKeywords(ZiArea $area, ZiGuidelineSubPoint $subPoint)
    {
        return collect([
            $area->code,
            $area->name,
            $area->description,
            optional($subPoint->point)->title,
            $subPoint->title,
            $subPoint->description,
        ])->merge($subPoint->indicators->pluck('indicator_text'))
            ->merge($subPoint->indicators->pluck('evidence_example'))
            ->merge($subPoint->indicators->pluck('implementation_note'))
            ->filter()
            ->flatMap(function ($text) {
                return preg_split('/[^\pL\pN]+/u', mb_strtolower((string) $text));
            })
            ->map(function ($token) {
                return trim($token);
            })
            ->filter(function ($token) {
                return mb_strlen($token) >= 4 && !in_array($token, ['yang', 'dengan', 'untuk', 'pada', 'atau', 'dari', 'area', 'indikator', 'kegiatan', 'poin'], true);
            })
            ->merge($this->buildAreaKeywordsFromArea($area))
            ->unique()
            ->values()
            ->all();
    }

    protected function buildAreaKeywords(ZiActivity $activity)
    {
        return $this->buildAreaKeywordsFromArea($activity->area);
    }

    protected function buildAreaKeywordsFromArea($area)
    {
        $haystack = mb_strtolower(trim(
            optional($area)->code . ' ' .
            optional($area)->name . ' ' .
            optional($area)->description
        ));

        if (str_contains($haystack, 'manajemen perubahan') || str_contains($haystack, 'ap1')) {
            return $this->areaKeywordMap['manajemen_perubahan'];
        }
        if (str_contains($haystack, 'tatalaksana') || str_contains($haystack, 'ap2')) {
            return $this->areaKeywordMap['tatalaksana'];
        }
        if (str_contains($haystack, 'sdm') || str_contains($haystack, 'manajemen sdm') || str_contains($haystack, 'ap3')) {
            return $this->areaKeywordMap['sdm'];
        }
        if (str_contains($haystack, 'akuntabilitas') || str_contains($haystack, 'ap4')) {
            return $this->areaKeywordMap['akuntabilitas'];
        }
        if (str_contains($haystack, 'pengawasan') || str_contains($haystack, 'ap5')) {
            return $this->areaKeywordMap['pengawasan'];
        }
        if (str_contains($haystack, 'pelayanan publik') || str_contains($haystack, 'ap6')) {
            return $this->areaKeywordMap['pelayanan_publik'];
        }

        return [];
    }

    protected function recommendSuratKeluar(array $keywords, User $user)
    {
        return $this->scoreCollection(
            SuratKeluar::visibleTo($user)->latest()->take(80)->get(),
            function ($item) { return mb_strtolower(trim(($item->perihal ?: '') . ' ' . ($item->deskripsi_kode ?: '') . ' ' . (optional($item->kategoriSurat)->nama ?: ''))); },
            function ($item, $score) { return ['linked_source' => 'surat_keluar:' . $item->id, 'type' => 'Surat Keluar', 'title' => ($item->nomor_surat_formatted ?: $item->nomor_surat) . ' - ' . $item->perihal, 'meta' => optional($item->tanggal_surat)->translatedFormat('d F Y'), 'score' => $score + $this->bonusForSuratKeluar($item)]; },
            $keywords
        );
    }

    protected function recommendSuratMasuk(array $keywords, User $user)
    {
        return $this->scoreCollection(
            SuratMasuk::visibleTo($user)->latest()->take(80)->get(),
            function ($item) { return mb_strtolower(trim(($item->perihal ?: '') . ' ' . ($item->pengirim ?: ''))); },
            function ($item, $score) { return ['linked_source' => 'surat_masuk:' . $item->id, 'type' => 'Surat Masuk', 'title' => ($item->nomor_surat ?: '-') . ' - ' . $item->perihal, 'meta' => optional($item->tanggal_surat)->translatedFormat('d F Y'), 'score' => $score]; },
            $keywords
        );
    }

    protected function recommendRapat(array $keywords, User $user)
    {
        return $this->scoreCollection(
            Rapat::visibleTo($user)->latest()->take(80)->get(),
            function ($item) { return mb_strtolower(trim(($item->judul ?: '') . ' ' . ($item->deskripsi ?: '') . ' ' . ($item->tempat ?: ''))); },
            function ($item, $score) { return ['linked_source' => 'rapat:' . $item->id, 'type' => 'Undangan Rapat', 'title' => ($item->nomor_undangan ?: 'Undangan') . ' - ' . $item->judul, 'meta' => optional($item->tanggal)->translatedFormat('d F Y'), 'score' => $score + $this->bonusForRapat($item)]; },
            $keywords
        );
    }

    protected function recommendNotulensi(array $keywords, User $user)
    {
        return $this->scoreCollection(
            RapatNotulensi::with('rapat')->whereHas('rapat', function ($query) use ($user) { $query->visibleTo($user); })->latest()->take(80)->get(),
            function ($item) { return mb_strtolower(trim(($item->judul ?: '') . ' ' . ($item->hasil_rapat ?: '') . ' ' . ($item->agenda_rapat ?: ''))); },
            function ($item, $score) { return ['linked_source' => 'rapat_notulensi:' . $item->id, 'type' => 'Notulensi', 'title' => 'Notulensi - ' . ($item->judul ?: optional($item->rapat)->judul), 'meta' => optional(optional($item->rapat)->tanggal)->translatedFormat('d F Y'), 'score' => $score + 1]; },
            $keywords
        );
    }

    protected function recommendLaporan(array $keywords, User $user)
    {
        return $this->scoreCollection(
            RapatLaporan::with('rapat')->where('jenis', 'tindak_lanjut')->whereHas('rapat', function ($query) use ($user) { $query->visibleTo($user); })->latest()->take(80)->get(),
            function ($item) { return mb_strtolower(trim(($item->judul ?: '') . ' ' . ($item->deskripsi ?: '') . ' ' . ($item->bab_2_hasil_monitoring ?: ''))); },
            function ($item, $score) { return ['linked_source' => 'rapat_laporan:' . $item->id, 'type' => 'Laporan Tindak Lanjut', 'title' => $item->judul, 'meta' => optional(optional($item->rapat)->tanggal)->translatedFormat('d F Y'), 'score' => $score + 2]; },
            $keywords
        );
    }

    protected function recommendCuti(array $keywords, User $user)
    {
        $leaveKeywords = ['sdm', 'pegawai', 'kepegawaian', 'cuti', 'administrasi', 'layanan'];
        if (count(array_intersect($keywords, $leaveKeywords)) === 0) {
            return collect();
        }

        $query = LeaveRequest::with(['user', 'leaveType'])->latest()->take(40);
        if (!$user->isSuperAdmin()) {
            $query->where(function ($builder) use ($user) {
                $builder->where('user_id', $user->id)
                    ->orWhereHas('approvals', function ($approvalQuery) use ($user) {
                        $approvalQuery->where('approver_id', $user->id);
                    });
            });
        }

        return $query->get()->map(function ($item) {
            return ['linked_source' => 'leave_request:' . $item->id, 'type' => 'Cuti', 'title' => ($item->display_number ?: 'Cuti') . ' - ' . optional($item->leaveType)->name . ' - ' . optional($item->user)->name, 'meta' => optional($item->start_date)->translatedFormat('d F Y'), 'score' => 1];
        });
    }

    protected function scoreCollection($collection, callable $haystackResolver, callable $mapResolver, array $keywords)
    {
        return $collection->map(function ($item) use ($haystackResolver, $mapResolver, $keywords) {
            $haystack = $haystackResolver($item);
            $score = 0;
            foreach ($keywords as $keyword) {
                if ($keyword !== '' && mb_strpos($haystack, $keyword) !== false) {
                    $score++;
                }
            }
            return $score > 0 ? $mapResolver($item, $score) : null;
        })->filter()->values();
    }

    protected function bonusForSuratKeluar($item)
    {
        $text = mb_strtolower(trim(($item->perihal ?: '') . ' ' . (optional($item->kategoriSurat)->nama ?: '')));
        if (str_contains($text, 'sk') || str_contains($text, 'surat tugas') || str_contains($text, 'edaran')) {
            return 2;
        }

        return 0;
    }

    protected function bonusForRapat($item)
    {
        $text = mb_strtolower(trim(($item->judul ?: '') . ' ' . ($item->deskripsi ?: '')));
        if (str_contains($text, 'monitoring') || str_contains($text, 'evaluasi') || str_contains($text, 'sosialisasi')) {
            return 2;
        }

        return 0;
    }
}
