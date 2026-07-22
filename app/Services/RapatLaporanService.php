<?php

namespace App\Services;

use App\DasarHukum;
use App\Rapat;
use App\RapatLaporan;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use setasign\Fpdi\Fpdi;

class RapatLaporanService
{
    public function ensureForRapats($rapats, $userId = null)
    {
        $rapats = $rapats instanceof Collection ? $rapats : collect($rapats);

        foreach ($rapats as $rapat) {
            $jenis = 'tindak_lanjut';
            $defaults = $this->defaultsFor($rapat, $jenis, $userId);
            $laporan = RapatLaporan::firstOrCreate(
                ['rapat_id' => $rapat->id, 'jenis' => $jenis],
                $defaults
            );

            $fill = [
                'judul' => $defaults['judul'],
                'deskripsi' => $defaults['deskripsi'],
                'is_ready' => $defaults['is_ready'],
                'updated_by' => $userId,
            ];

            foreach ([
                'bab_1_latar_belakang',
                'bab_1_dasar',
                'bab_1_tujuan',
                'bab_2_hasil_monitoring',
                'bab_3_tindak_lanjut',
            ] as $field) {
                if (empty($laporan->{$field})) {
                    $fill[$field] = $defaults[$field] ?? null;
                }
            }

            $laporan->fill($fill);

            if ($laporan->status !== 'arsip') {
                $laporan->status = 'aktif';
            }

            $laporan->save();
        }
    }

    public function defaultsFor(Rapat $rapat, $jenis, $userId = null)
    {
        $defaultSections = $this->buildDefaultSections($rapat);

        return array_merge([
            'judul' => $this->buildTitle($rapat, $jenis),
            'deskripsi' => 'Laporan tindak lanjut manual yang akan digabung dengan undangan, absensi, dan notulensi.',
            'status' => 'aktif',
            'is_ready' => true,
            'created_by' => $userId,
            'updated_by' => $userId,
        ], $defaultSections);
    }

    public function buildTitle(Rapat $rapat, $jenis)
    {
        return 'Laporan Tindak Lanjut - ' . $rapat->judul;
    }

    public function buildDefaultSections(Rapat $rapat)
    {
        return [
            'bab_1_latar_belakang' => $this->buildDefaultLatarBelakangForForm($rapat),
            'bab_1_dasar' => $this->buildDefaultDasarForForm($rapat),
            'bab_1_tujuan' => $this->buildDefaultTujuanForForm($rapat),
            'bab_2_hasil_monitoring' => $this->buildDefaultBab2ForForm($rapat),
            'bab_3_tindak_lanjut' => $this->buildDefaultBab3ForForm($rapat),
        ];
    }

    public function generateMergedTindakLanjutPdf(RapatLaporan $laporan)
    {
        $laporan->load([
            'rapat.kategoriSuratKode',
            'rapat.creator',
            'rapat.pesertas.jabatan',
            'rapat.attendances.user',
            'rapat.internalAttendances',
            'rapat.guestAttendances',
            'rapat.notulensi.notulis',
            'rapat.notulensi.tindakLanjuts.user',
            'rapat.notulensi.approval.approver.jabatan',
            'rapat.approver1.jabatan',
            'rapat.approver2.jabatan',
            'rapat.approvals.approver.jabatan',
            'rapat.suratKeluar',
        ]);

        $rapat = $laporan->rapat;
        $documentService = app(RapatDocumentService::class);

        $sourceFiles = [];
        $tempFiles = [];
        $verifier = app(PdfVerificationService::class);
        $verification = $verifier->begin('rapat', 'laporan_tindak_lanjut_final', $laporan->id, $laporan->judul ?: 'Laporan Tindak Lanjut', [], [
            'rapat_id' => $rapat->id,
        ]);

        try {
            if ($rapat->kategori_surat_kode_id) {
                try {
                    $undanganTemp = $documentService->createUndanganTempFile($rapat, 'laporan-undangan');
                    $sourceFiles[] = $undanganTemp['path'];
                    $tempFiles[] = $undanganTemp['path'];
                } catch (\Throwable $e) {
                    // Laporan tetap dapat dibuat meskipun undangan gagal dirender.
                }
            }

            $absensiTempPath = $this->makeTempPdfPath('laporan-absensi-' . $laporan->id);
            File::put($absensiTempPath, $this->renderAbsensiPdf($rapat));
            $tempFiles[] = $absensiTempPath;
            $sourceFiles[] = $absensiTempPath;

            $notulensiTemp = $this->resolveNotulensiPdfPath($rapat->notulensi);
            if ($notulensiTemp) {
                $sourceFiles[] = $notulensiTemp['path'];
                if (!empty($notulensiTemp['temporary'])) {
                    $tempFiles[] = $notulensiTemp['path'];
                }
            }

            $laporanTempPath = $this->makeTempPdfPath('laporan-manual-' . $laporan->id);
            File::put($laporanTempPath, $this->renderLaporanTindakLanjutPdf($laporan, $verifier->viewData($verification)));
            $tempFiles[] = $laporanTempPath;
            $sourceFiles[] = $laporanTempPath;

            $mergedTempPath = $this->makeTempPdfPath('laporan-final-' . $laporan->id);
            $this->mergePdfFiles($sourceFiles, $mergedTempPath);
            $tempFiles[] = $mergedTempPath;

            $filename = 'rapat/laporan/laporan-tindak-lanjut-' . $laporan->id . '.pdf';

            $binary = File::get($mergedTempPath);
            $verifier->finalize($verification, $binary, basename($filename));

            $laporan->update([
                'file_path' => null,
                'file_nama' => 'laporan-tindak-lanjut-' . Str::slug($rapat->judul) . '.pdf',
                'file_mime' => 'application/pdf',
                'file_size' => strlen($binary),
                'is_ready' => true,
                'generated_at' => now('Asia/Jayapura'),
            ]);

            return $laporan->fresh();
        } finally {
            foreach ($tempFiles as $tempFile) {
                if ($tempFile && file_exists($tempFile)) {
                    @unlink($tempFile);
                }
            }
        }
    }

    protected function renderAbsensiPdf(Rapat $rapat)
    {
        $attendanceByUser = $rapat->internalAttendances->keyBy('user_id');
        $internalParticipants = $rapat->pesertas->map(function ($participant) use ($attendanceByUser) {
            $attendance = $attendanceByUser->get($participant->id);

            return [
                'user' => $participant,
                'attendance' => $attendance,
                'signature_data_uri' => $attendance ? $this->attendanceQr($attendance) : null,
            ];
        });

        $guestAttendances = $rapat->guestAttendances->sortBy('attended_at')->values()->map(function ($attendance) {
            $attendance->signature_data_uri = $this->attendanceQr($attendance);
            return $attendance;
        });

        $attendanceRows = $internalParticipants->map(function ($item) {
            return [
                'name' => $item['user']->name,
                'description' => $item['user']->jabatan_keterangan ?: optional($item['user']->jabatan)->nama ?: '-',
                'status' => $item['attendance'] ? 'Hadir' : 'Belum Hadir',
                'signature_data_uri' => $item['signature_data_uri'],
            ];
        })->concat($guestAttendances->map(function ($attendance) {
            return [
                'name' => $attendance->participant_name_snapshot,
                'description' => $attendance->guest_instansi ?: ($attendance->participant_jabatan_snapshot ?: '-'),
                'status' => 'Hadir',
                'signature_data_uri' => $attendance->signature_data_uri,
            ];
        }))->values();

        $attendanceCompleted = $rapat->status === 'selesai';
        $pimpinanSignature = app(RapatDocumentService::class)->buildApprovalSignatureData($rapat, $attendanceCompleted);
        $pdfVerification = null;

        $kopImage = $this->resolvePublicImage(['kop_absen.jpg', 'kop_absen.jpeg', 'kop_absen.png']);

        return PDF::loadView('rapat.absensi.pdf', compact(
            'rapat',
            'attendanceRows',
            'attendanceCompleted',
            'pimpinanSignature',
            'kopImage',
            'pdfVerification'
        ))->setPaper('a4', 'portrait')->output();
    }

    protected function resolveNotulensiPdfPath($notulensi)
    {
        if (!$notulensi) {
            return null;
        }

        if ($notulensi->mode === 'upload' && $notulensi->file_path && Storage::disk('public')->exists($notulensi->file_path)) {
            return [
                'path' => Storage::disk('public')->path($notulensi->file_path),
                'temporary' => false,
            ];
        }

        $notulensi->loadMissing([
            'rapat.kategoriSuratKode',
            'rapat.creator',
            'rapat.pesertas.jabatan',
            'rapat.approver1',
            'rapat.approver2',
            'notulis',
            'tindakLanjuts.user',
            'approval.approver.jabatan',
        ]);

        $documentService = app(RapatDocumentService::class);
        $notulensiController = app(\App\Http\Controllers\RapatNotulensiController::class);

        $pdf = PDF::loadView('rapat.notulensi.pdf', [
            'notulensi' => $notulensi,
            'rapat' => $notulensi->rapat,
            'kopImage' => $this->resolvePublicImage(['kop_absen.jpg', 'kop_undangan.png', 'kop_undangan.jpg', 'kop_undangan.jpeg']),
            'dokumentasiImages' => $notulensiController->resolveDocumentationImagesForExport($notulensi),
            'uraianKegiatanRows' => $notulensiController->resolveUraianKegiatanRowsForExport($notulensi),
            'notulisSignature' => $documentService->buildNotulensiSignatureData($notulensi),
            'approvalSignature' => $documentService->buildNotulensiApprovalSignatureData($notulensi),
        ])->setPaper('a4', 'portrait');

        $tempPath = $this->makeTempPdfPath('laporan-notulensi-' . $notulensi->id);
        File::put($tempPath, $pdf->output());

        return [
            'path' => $tempPath,
            'temporary' => true,
        ];
    }

    protected function renderLaporanTindakLanjutPdf(RapatLaporan $laporan, array $pdfVerification = null)
    {
        $rapat = $laporan->rapat;

        return PDF::loadView('rapat.laporan.pdf.tindak-lanjut', [
            'laporan' => $laporan,
            'rapat' => $rapat,
            'coverLogo' => $this->resolvePublicImage(['logo_qr.png']),
            'bab1LatarBelakang' => $laporan->bab_1_latar_belakang,
            'bab1Dasar' => $laporan->bab_1_dasar,
            'bab1Tujuan' => $laporan->bab_1_tujuan,
            'bab2HasilMonitoring' => $laporan->bab_2_hasil_monitoring,
            'bab3TindakLanjut' => $laporan->bab_3_tindak_lanjut,
            'pdfVerification' => $pdfVerification,
        ])->setPaper('a4', 'portrait')->output();
    }

    protected function mergePdfFiles(array $sourceFiles, $outputPath)
    {
        $pdf = new Fpdi();

        foreach ($sourceFiles as $sourceFile) {
            if (!$sourceFile || !file_exists($sourceFile)) {
                continue;
            }

            $pageCount = $pdf->setSourceFile($sourceFile);

            for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
                $templateId = $pdf->importPage($pageNumber);
                $size = $pdf->getTemplateSize($templateId);
                $orientation = $size['width'] > $size['height'] ? 'L' : 'P';

                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);
            }
        }

        $pdf->Output('F', $outputPath);
    }

    protected function makeTempPdfPath($prefix)
    {
        $dir = storage_path('app/temp');
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        return $dir . DIRECTORY_SEPARATOR . $prefix . '-' . Str::uuid() . '.pdf';
    }

    protected function attendanceQr($attendance)
    {
        $url = URL::signedRoute('rapat.attendance.verify', ['attendance' => $attendance->id]);

        return app(DocumentQrCodeService::class)->dataUri($url, 104);
    }

    protected function resolvePublicImage(array $filenames)
    {
        foreach ($filenames as $filename) {
            $path = public_path($filename);
            if (is_file($path)) {
                $mime = mime_content_type($path) ?: 'image/png';
                return 'data:' . $mime . ';base64,' . base64_encode(File::get($path));
            }
        }

        return null;
    }

    public function buildDefaultLatarBelakangForForm(Rapat $rapat)
    {
        return '<p>' . e($this->buildLatarBelakang($rapat)) . '</p>';
    }

    public function buildDefaultDasarForForm(Rapat $rapat)
    {
        $items = $this->buildDasarHukum($rapat);
        if (empty($items)) {
            return '<ol><li>-</li></ol>';
        }

        $html = '<ol>';
        foreach ($items as $item) {
            $html .= '<li>' . e($item) . '</li>';
        }
        $html .= '</ol>';

        return $html;
    }

    public function buildDefaultTujuanForForm(Rapat $rapat)
    {
        return '<p>' . e($this->buildTujuan($rapat)) . '</p>';
    }

    public function buildDefaultBab2ForForm(Rapat $rapat)
    {
        return optional($rapat->notulensi)->hasil_rapat ?: '<p>Belum ada hasil monitoring dan evaluasi yang tercatat pada notulensi.</p>';
    }

    public function buildDefaultBab3ForForm(Rapat $rapat)
    {
        $opening = '<p>' . e($this->buildTindakLanjutOpening($rapat)) . '</p>';
        $items = $this->buildRecommendationEvidenceMap(optional($rapat)->notulensi);

        if ($items->isEmpty()) {
            return $opening . '<p>Belum ada rekomendasi dan tindak lanjut yang tercatat untuk kegiatan ini.</p>';
        }

        $html = $opening . '<ol>';
        foreach ($items as $item) {
            $html .= '<li>';
            $html .= $item['aksi'] ?: '-';

            if ($item['evidences']->isNotEmpty()) {
                $html .= '<p><strong>Bukti eviden tindak lanjut:</strong></p><ol>';
                foreach ($item['evidences'] as $index => $evidence) {
                    $html .= '<li><a href="' . e($evidence['public_url']) . '">Eviden ' . ($index + 1) . '</a></li>';
                }
                $html .= '</ol>';
            } elseif (($item['pending_count'] ?? 0) > 0) {
                $html .= '<p>Eviden tindak lanjut belum diunggah.</p>';
            } else {
                $html .= '<p>Belum ada eviden tindak lanjut yang tercatat.</p>';
            }

            $html .= '</li>';
        }
        $html .= '</ol>';

        return $html;
    }

    public function buildLatarBelakang(Rapat $rapat)
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

    public function buildDasarHukum(Rapat $rapat)
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

    protected function resolveMasterDasarHukum(Rapat $rapat)
    {
        $context = Str::lower(trim(implode(' ', array_filter([
            $rapat->judul,
            $rapat->deskripsi,
            optional($rapat->kategoriSuratKode)->nama,
            optional($rapat->kategoriSuratKode)->kode,
        ]))));

        return DasarHukum::with('kategoriSuratKode')
            ->where('aktif', true)
            ->orderBy('urutan')
            ->orderBy('tema')
            ->get()
            ->filter(function ($item) use ($rapat, $context) {
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
            })
            ->pluck('uraian')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function buildTujuan(Rapat $rapat)
    {
        return 'Tujuan penyusunan laporan tindak lanjut ini adalah untuk mendokumentasikan hasil ' . $rapat->judul . ', memastikan setiap rekomendasi ditindaklanjuti oleh penanggung jawab, serta menyediakan bukti pelaksanaan tindak lanjut secara terukur dan dapat dipertanggungjawabkan.';
    }

    public function buildTindakLanjutOpening(Rapat $rapat)
    {
        return 'Berdasarkan hasil monitoring dan evaluasi pada kegiatan ' . $rapat->judul . ', berikut disampaikan tindak lanjut dan rekomendasi yang perlu dilaksanakan beserta tautan eviden pendukung yang telah diunggah.';
    }

    public function buildRecommendationEvidenceMap($notulensi)
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
