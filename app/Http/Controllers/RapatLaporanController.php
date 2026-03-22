<?php

namespace App\Http\Controllers;

use App\DasarHukum;
use App\Http\Requests\StoreRapatLaporanRequest;
use App\Http\Requests\UploadRapatLaporanRequest;
use App\RapatLaporan;
use App\Services\RapatLaporanService;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RapatLaporanController extends Controller
{
    protected $laporanService;

    public function __construct(RapatLaporanService $laporanService)
    {
        $this->middleware('auth');
        $this->laporanService = $laporanService;
    }

    public function index()
    {
        abort_unless(auth()->user()->canAccessMeetingModule(), 403);

        $rapats = \App\Rapat::visibleTo(auth()->user())
            ->with([
                'kategoriSuratKode',
                'creator',
                'notulensi.notulis',
                'attendances',
                'laporans',
            ])
            ->orderByDesc('tanggal')
            ->orderByDesc('waktu_mulai')
            ->get();

        $this->laporanService->ensureForRapats($rapats, auth()->id());

        $laporans = RapatLaporan::with(['rapat.kategoriSuratKode', 'rapat.creator', 'rapat.notulensi'])
            ->whereIn('rapat_id', $rapats->pluck('id'))
            ->where('jenis', 'tindak_lanjut')
            ->whereNull('archived_at')
            ->orderByDesc('updated_at')
            ->get();

        return view('rapat.laporan.index', compact('laporans'));
    }

    public function arsip()
    {
        abort_unless(auth()->user()->canAccessMeetingModule(), 403);

        $rapatIds = \App\Rapat::visibleTo(auth()->user())->pluck('id');

        $laporans = RapatLaporan::with(['rapat.kategoriSuratKode', 'rapat.creator'])
            ->whereIn('rapat_id', $rapatIds)
            ->where('jenis', 'tindak_lanjut')
            ->whereNotNull('archived_at')
            ->orderByDesc('archived_at')
            ->get();

        return view('rapat.laporan.arsip', compact('laporans'));
    }

    public function preview(RapatLaporan $laporan)
    {
        abort_unless(auth()->user()->canViewRapat($laporan->rapat), 403);

        if ($laporan->jenis === 'tindak_lanjut' && !$laporan->file_path) {
            $laporan = $this->laporanService->generateMergedTindakLanjutPdf($laporan);
        }

        if ($laporan->file_path) {
            return response()->file(storage_path('app/public/' . $laporan->file_path));
        }

        return $this->buildPdfResponse($laporan, false);
    }

    public function download(RapatLaporan $laporan)
    {
        abort_unless(auth()->user()->canViewRapat($laporan->rapat), 403);

        if ($laporan->jenis === 'tindak_lanjut' && !$laporan->file_path) {
            $laporan = $this->laporanService->generateMergedTindakLanjutPdf($laporan);
        }

        if ($laporan->file_path) {
            return response()->download(storage_path('app/public/' . $laporan->file_path), $laporan->file_nama ?: basename($laporan->file_path));
        }

        return $this->buildPdfResponse($laporan, true);
    }

    public function upload(UploadRapatLaporanRequest $request, RapatLaporan $laporan)
    {
        abort_unless(auth()->user()->canAccessMeetingMinutes(), 403);

        $file = $request->file('laporan_file');

        if ($laporan->file_path) {
            Storage::disk('public')->delete($laporan->file_path);
        }

        $path = $file->store('rapat/laporan', 'public');

        $laporan->update([
            'file_path' => $path,
            'file_nama' => $file->getClientOriginalName(),
            'file_mime' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'deskripsi' => $request->input('deskripsi_upload') ?: $laporan->deskripsi,
            'updated_by' => auth()->id(),
            'is_ready' => true,
            'status' => $laporan->archived_at ? 'arsip' : 'aktif',
        ]);

        return back()->with('success', 'File laporan berhasil diupload sebagai file final.');
    }

    public function archive(RapatLaporan $laporan)
    {
        abort_unless(auth()->user()->canAccessMeetingMinutes(), 403);

        $laporan->update([
            'archived_at' => now(),
            'status' => 'arsip',
            'updated_by' => auth()->id(),
        ]);

        return back()->with('success', 'Laporan berhasil diarsipkan.');
    }

    public function unarchive(RapatLaporan $laporan)
    {
        abort_unless(auth()->user()->canAccessMeetingMinutes(), 403);

        $laporan->update([
            'archived_at' => null,
            'status' => 'aktif',
            'updated_by' => auth()->id(),
        ]);

        return back()->with('success', 'Laporan berhasil dikembalikan ke daftar aktif.');
    }

    protected function buildPdfResponse(RapatLaporan $laporan, $download = false)
    {
        $laporan->load([
            'rapat.kategoriSuratKode',
            'rapat.creator',
            'rapat.pesertas',
            'rapat.attendances',
            'rapat.notulensi.notulis',
            'rapat.notulensi.tindakLanjuts.user',
        ]);

        if ($laporan->jenis === 'tindak_lanjut') {
            $view = 'rapat.laporan.pdf.tindak-lanjut';
        } else {
            $view = 'rapat.laporan.pdf.gabungan';
        }

        $pdf = PDF::loadView($view, [
            'laporan' => $laporan,
            'rapat' => $laporan->rapat,
            'notulensi' => $laporan->rapat->notulensi,
            'attendances' => $laporan->rapat->attendances,
            'coverLogo' => $this->resolvePublicImage('logo_qr.png'),
            'latarBelakang' => $this->buildLatarBelakang($laporan->rapat),
            'dasarHukum' => $this->buildDasarHukum($laporan->rapat),
            'tujuanLaporan' => $this->buildTujuan($laporan->rapat),
            'tindakLanjutOpening' => $this->buildTindakLanjutOpening($laporan->rapat),
            'rekomendasiItems' => $this->buildRecommendationEvidenceMap($laporan->rapat->notulensi),
        ])->setPaper('a4', 'portrait');

        $filename = str_replace(' ', '-', strtolower($laporan->judul)) . '.pdf';

        return $download ? $pdf->download($filename) : $pdf->stream($filename);
    }

    public function edit(RapatLaporan $laporan)
    {
        abort_unless(auth()->user()->canAccessMeetingMinutes(), 403);
        abort_unless($laporan->jenis === 'tindak_lanjut', 404);

        $laporan->load([
            'rapat.kategoriSuratKode',
            'rapat.creator',
            'rapat.pesertas',
            'rapat.notulensi.notulis',
            'rapat.attendances',
        ]);

        $defaults = $this->laporanService->buildDefaultSections($laporan->rapat);

        return view('rapat.laporan.form', [
            'laporan' => $laporan,
            'rapat' => $laporan->rapat,
            'pageTitle' => 'Edit Laporan Tindak Lanjut',
            'formAction' => route('rapat.laporan.update', $laporan),
            'defaultSections' => $defaults,
        ]);
    }

    public function update(StoreRapatLaporanRequest $request, RapatLaporan $laporan)
    {
        abort_unless(auth()->user()->canAccessMeetingMinutes(), 403);
        abort_unless($laporan->jenis === 'tindak_lanjut', 404);

        $laporan->update([
            'judul' => $request->input('judul'),
            'bab_1_latar_belakang' => $request->input('bab_1_latar_belakang'),
            'bab_1_dasar' => $request->input('bab_1_dasar'),
            'bab_1_tujuan' => $request->input('bab_1_tujuan'),
            'bab_2_hasil_monitoring' => $request->input('bab_2_hasil_monitoring'),
            'bab_3_tindak_lanjut' => $request->input('bab_3_tindak_lanjut'),
            'deskripsi' => 'Laporan tindak lanjut manual yang digabung dengan undangan, absensi, dan notulensi.',
            'updated_by' => auth()->id(),
            'is_ready' => true,
        ]);

        $this->laporanService->generateMergedTindakLanjutPdf($laporan->fresh(['rapat.notulensi.tindakLanjuts', 'rapat.attendances']));

        return redirect()->route('rapat.laporan.edit', $laporan)->with('success', 'Laporan tindak lanjut berhasil disimpan dan file PDF gabungan berhasil digenerate.');
    }

    public function buildDefaultLatarBelakangForForm($rapat)
    {
        return $this->laporanService->buildDefaultLatarBelakangForForm($rapat);
    }

    public function buildDefaultDasarForForm($rapat)
    {
        return $this->laporanService->buildDefaultDasarForForm($rapat);
    }

    public function buildDefaultTujuanForForm($rapat)
    {
        return $this->laporanService->buildDefaultTujuanForForm($rapat);
    }

    public function buildDefaultBab2ForForm($rapat)
    {
        return $this->laporanService->buildDefaultBab2ForForm($rapat);
    }

    public function buildDefaultBab3ForForm($rapat)
    {
        return $this->laporanService->buildDefaultBab3ForForm($rapat);
    }

    protected function resolvePublicImage($filename)
    {
        $path = public_path($filename);
        if (!is_file($path)) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'image/png';
        return 'data:' . $mime . ';base64,' . base64_encode(File::get($path));
    }

    protected function buildLatarBelakang($rapat)
    {
        $context = Str::lower(trim(implode(' ', array_filter([
            $rapat->judul,
            $rapat->deskripsi,
            optional($rapat->kategoriSuratKode)->nama,
            optional($rapat->kategoriSuratKode)->kode,
        ]))));

        $judul = trim((string) $rapat->judul);
        $deskripsi = trim(strip_tags((string) $rapat->deskripsi));

        $opening = 'Kegiatan ' . $judul . ' dilaksanakan sebagai bagian dari upaya penguatan koordinasi, sinkronisasi pelaksanaan program kerja, serta peningkatan kualitas tata kelola di lingkungan Pengadilan Tinggi Agama Papua Barat.';

        if ($this->containsAny($context, ['zona integritas', 'zi', 'wbk', 'wbbm', 'reformasi birokrasi'])) {
            $opening = 'Kegiatan ' . $judul . ' dilaksanakan dalam rangka memperkuat pembangunan Zona Integritas, menjaga kesinambungan pelaksanaan reformasi birokrasi, dan memastikan setiap area perubahan berjalan secara terukur di lingkungan Pengadilan Tinggi Agama Papua Barat.';
        } elseif ($this->containsAny($context, ['informasi publik', 'ppid', 'keterbukaan informasi', 'website', 'media informasi'])) {
            $opening = 'Kegiatan ' . $judul . ' dilaksanakan untuk memperkuat pelaksanaan keterbukaan informasi publik, meningkatkan kualitas layanan informasi, dan memastikan pengelolaan media informasi serta website satuan kerja berjalan sesuai ketentuan yang berlaku.';
        } elseif ($this->containsAny($context, ['kepegawaian', 'pegawai', 'asn', 'pppk', 'cpns', 'pelantikan', 'mutasi', 'promosi'])) {
            $opening = 'Kegiatan ' . $judul . ' dilaksanakan untuk mendukung tertib administrasi dan pengelolaan manajemen kepegawaian, memastikan proses pembinaan aparatur berjalan objektif, serta menyelaraskan langkah kerja terkait kebutuhan organisasi.';
        } elseif ($this->containsAny($context, ['keuangan', 'anggaran', 'dipa', 'realisasi', 'pagu', 'laporan keuangan', 'rka', 'sakti'])) {
            $opening = 'Kegiatan ' . $judul . ' dilaksanakan untuk memastikan pengelolaan anggaran, penyerapan belanja, dan pelaksanaan kegiatan keuangan berjalan efektif, akuntabel, serta sesuai prioritas program kerja satuan kerja.';
        } elseif ($this->containsAny($context, ['arsip', 'persuratan', 'surat', 'naskah dinas'])) {
            $opening = 'Kegiatan ' . $judul . ' dilaksanakan untuk mewujudkan tertib administrasi persuratan dan pengelolaan arsip, sekaligus memperkuat keseragaman pelaksanaan tata naskah dinas di lingkungan Pengadilan Tinggi Agama Papua Barat.';
        } elseif ($this->containsAny($context, ['teknologi informasi', 'aplikasi', 'sistem', 'digital', 'website', 'server', 'jaringan'])) {
            $opening = 'Kegiatan ' . $judul . ' dilaksanakan untuk meningkatkan kualitas pengelolaan teknologi informasi, menjaga keberlangsungan layanan digital, dan memastikan sistem pendukung kerja organisasi berjalan optimal.';
        } elseif ($this->containsAny($context, ['monitoring', 'evaluasi', 'monev', 'pengawasan', 'pemantauan'])) {
            $opening = 'Kegiatan ' . $judul . ' dilaksanakan sebagai sarana monitoring dan evaluasi atas pelaksanaan program kerja, untuk mengidentifikasi capaian, kendala, dan langkah perbaikan yang perlu segera ditindaklanjuti.';
        } elseif ($this->containsAny($context, ['fit and proper', 'seleksi', 'uji kelayakan', 'wawancara'])) {
            $opening = 'Kegiatan ' . $judul . ' dilaksanakan untuk mendukung proses penilaian, pendalaman, dan pengambilan keputusan secara objektif terhadap peserta sesuai kebutuhan organisasi dan ketentuan yang berlaku.';
        }

        $detail = '';
        if ($deskripsi !== '') {
            $detail = ' Materi kegiatan ini secara khusus membahas ' . $deskripsi;
            if (!Str::endsWith($detail, '.')) {
                $detail .= '.';
            }
        }

        $closing = ' Melalui kegiatan ini diharapkan tersusun langkah kerja yang lebih terarah, solusi atas permasalahan yang dihadapi, serta tindak lanjut yang dapat dilaksanakan secara terukur dan bertanggung jawab.';

        return trim($opening . $detail . $closing);
    }

    protected function buildDasarHukum($rapat)
    {
        $masterDasar = $this->resolveMasterDasarHukum($rapat);
        if (!empty($masterDasar)) {
            return $masterDasar;
        }

        $context = Str::lower(trim(implode(' ', array_filter([
            $rapat->judul,
            $rapat->deskripsi,
            optional($rapat->kategoriSuratKode)->nama,
        ]))));

        $dasar = [
            'Undang-Undang Nomor 48 Tahun 2009 tentang Kekuasaan Kehakiman.',
            'Undang-Undang Nomor 7 Tahun 1989 tentang Peradilan Agama sebagaimana telah diubah terakhir dengan Undang-Undang Nomor 50 Tahun 2009.',
        ];

        if ($this->containsAny($context, ['zona integritas', 'zi', 'wbk', 'wbbm', 'reformasi birokrasi'])) {
            $dasar[] = 'Peraturan Presiden Nomor 81 Tahun 2010 tentang Grand Design Reformasi Birokrasi 2010-2025.';
            $dasar[] = 'Pedoman pembangunan Zona Integritas menuju Wilayah Bebas dari Korupsi dan Wilayah Birokrasi Bersih dan Melayani pada instansi pemerintah.';
        }

        if ($this->containsAny($context, ['informasi publik', 'ppid', 'keterbukaan informasi', 'website', 'media informasi'])) {
            $dasar[] = 'Undang-Undang Nomor 14 Tahun 2008 tentang Keterbukaan Informasi Publik.';
            $dasar[] = 'Kebijakan pengelolaan layanan informasi publik dan media informasi pada Pengadilan Tinggi Agama Papua Barat.';
        }

        if ($this->containsAny($context, ['kepegawaian', 'pegawai', 'asn', 'pppk', 'cpns', 'pelantikan', 'mutasi'])) {
            $dasar[] = 'Peraturan perundang-undangan yang berlaku di bidang manajemen aparatur sipil negara dan pembinaan kepegawaian.';
        }

        if ($this->containsAny($context, ['keuangan', 'anggaran', 'dipa', 'realisasi', 'pagu', 'laporan keuangan'])) {
            $dasar[] = 'Ketentuan pengelolaan keuangan negara dan pelaksanaan anggaran yang berlaku.';
        }

        if ($this->containsAny($context, ['arsip', 'persuratan', 'surat', 'naskah dinas'])) {
            $dasar[] = 'Ketentuan tata naskah dinas dan pengelolaan arsip di lingkungan peradilan agama.';
        }

        if ($this->containsAny($context, ['teknologi informasi', 'aplikasi', 'sistem', 'digital', 'website'])) {
            $dasar[] = 'Kebijakan internal pengelolaan teknologi informasi, aplikasi, dan sistem layanan pada Pengadilan Tinggi Agama Papua Barat.';
        }

        if ($this->containsAny($context, ['monitoring', 'evaluasi', 'monev', 'pengawasan'])) {
            $dasar[] = 'Program kerja, hasil monitoring sebelumnya, dan arahan pimpinan terkait pelaksanaan monitoring dan evaluasi ' . $rapat->judul . '.';
        }

        if ($this->containsAny($context, ['fit and proper', 'uji kelayakan', 'uji kepatutan', 'seleksi'])) {
            $dasar[] = 'Ketentuan internal mengenai seleksi, penilaian kelayakan, kepatutan, dan evaluasi penugasan sesuai kebutuhan organisasi.';
        }

        $dasar[] = 'Undangan rapat Nomor ' . ($rapat->nomor_undangan ?: '-') . ' sebagai dasar pelaksanaan kegiatan.';

        return collect($dasar)->filter()->unique()->values()->all();
    }

    protected function resolveMasterDasarHukum($rapat)
    {
        $context = Str::lower(trim(implode(' ', array_filter([
            $rapat->judul,
            $rapat->deskripsi,
            optional($rapat->kategoriSuratKode)->nama,
            optional($rapat->kategoriSuratKode)->kode,
        ]))));

        $query = DasarHukum::with('kategoriSuratKode')
            ->where('aktif', true)
            ->orderBy('urutan')
            ->orderBy('tema');

        $records = $query->get()->filter(function ($item) use ($rapat, $context) {
            $hasCategory = !empty($item->kategori_surat_kode_id);
            $categoryMatch = $hasCategory && (int) $item->kategori_surat_kode_id === (int) $rapat->kategori_surat_kode_id;

            $keywords = collect(explode(',', (string) $item->kata_kunci))
                ->map(function ($keyword) {
                    return Str::lower(trim($keyword));
                })
                ->filter();

            $hasKeywords = $keywords->isNotEmpty();
            $keywordMatch = $hasKeywords
                ? $keywords->contains(function ($keyword) use ($context) {
                    return $keyword !== '' && Str::contains($context, $keyword);
                })
                : false;

            if (!$hasCategory && !$hasKeywords) {
                return true;
            }

            return $categoryMatch || $keywordMatch;
        })->values();

        return $records->pluck('uraian')->filter()->unique()->values()->all();
    }

    protected function buildTujuan($rapat)
    {
        return 'Tujuan penyusunan laporan tindak lanjut ini adalah untuk mendokumentasikan hasil ' . $rapat->judul . ', memastikan setiap rekomendasi ditindaklanjuti oleh penanggung jawab, serta menyediakan bukti pelaksanaan tindak lanjut secara terukur dan dapat dipertanggungjawabkan.';
    }

    protected function buildTindakLanjutOpening($rapat)
    {
        return 'Berdasarkan hasil monitoring dan evaluasi pada kegiatan ' . $rapat->judul . ', berikut disampaikan tindak lanjut dan rekomendasi yang perlu dilaksanakan beserta tautan eviden pendukung yang telah diunggah.';
    }

    protected function buildRecommendationEvidenceMap($notulensi)
    {
        if (!$notulensi) {
            return collect();
        }

        $items = collect($notulensi->rekomendasi_items ?: []);
        $followUps = $notulensi->tindakLanjuts ?: collect();

        return $items->values()->map(function ($item, $index) use ($followUps) {
            $assigned = $followUps->where('item_index', $index)->values();

            return [
                'aksi' => $item['aksi'] ?? '-',
                'evidences' => $assigned->map(function ($followUp) {
                    return [
                        'status' => $followUp->status,
                        'status_label' => $followUp->status === 'completed'
                            ? 'Selesai'
                            : ($followUp->status === 'process' ? 'Proses' : 'Belum Ditindaklanjuti'),
                        'public_url' => $followUp->public_evidence_url,
                    ];
                })->filter(function ($item) {
                    return !empty($item['public_url']);
                })->values(),
                'pending_count' => $assigned->filter(function ($followUp) {
                    return empty($followUp->public_evidence_url);
                })->count(),
            ];
        });
    }

    protected function containsAny($haystack, array $needles)
    {
        foreach ($needles as $needle) {
            if (Str::contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }
}
