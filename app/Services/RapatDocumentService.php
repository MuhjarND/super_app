<?php

namespace App\Services;

use App\KategoriSurat;
use App\KlasifikasiKode;
use App\Rapat;
use App\RapatNotulensi;
use App\RapatNotulensiApproval;
use App\SuratKeluar;
use App\User;
use Barryvdh\DomPDF\Facade as PDF;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use setasign\Fpdi\Fpdi;

class RapatDocumentService
{
    protected $signaturePadService;
    protected $pdfVerificationService;

    public function __construct(SignaturePadService $signaturePadService, PdfVerificationService $pdfVerificationService)
    {
        $this->signaturePadService = $signaturePadService;
        $this->pdfVerificationService = $pdfVerificationService;
    }

    public function getKategoriSuratLeafOptions()
    {
        $leafs = KlasifikasiKode::with('parent.parent.parent')
            ->whereDoesntHave('children')
            ->orderBy('kode')
            ->get();

        return $leafs->map(function (KlasifikasiKode $item) {
            $chain = $this->resolveHierarchy($item);
            if (!$chain['klasifikasi'] || !preg_match('/[A-Za-z]/', (string) $chain['klasifikasi']->kode)) {
                return null;
            }

            $path = collect([$chain['klasifikasi'], $chain['fungsi'], $chain['kegiatan'], $chain['transaksi']])
                ->filter()
                ->map(function ($node) {
                    return $node->kode . ' - ' . $node->nama;
                })
                ->implode(' > ');

            return [
                'id' => $item->id,
                'kode' => $item->kode,
                'nama' => $item->nama,
                'tipe' => $item->tipe,
                'path' => $path,
                'label' => $item->kode . ' - ' . $item->nama,
                'full_label' => $path,
                'butuh_pakaian' => (bool) optional($this->findKategoriSuratByHierarchy($chain))->butuh_pakaian,
            ];
        })->filter()->sortBy(function ($item) {
            return $item['full_label'];
        })->values();
    }

    public function previewNomorSurat($kategoriSuratKodeId, $tanggal, $nomenklatur = null)
    {
        $category = KlasifikasiKode::find($kategoriSuratKodeId);
        if (!$category) {
            return '-';
        }

        $hierarchy = $this->resolveHierarchy($category);
        if (!$hierarchy['klasifikasi'] || !preg_match('/[A-Za-z]/', (string) $hierarchy['klasifikasi']->kode)) {
            return '-';
        }

        $issueDate = $tanggal ? Carbon::parse($tanggal, 'Asia/Jayapura') : Carbon::now('Asia/Jayapura');
        $nomenklatur = $this->normalizeNomenklatur($nomenklatur);

        $result = SuratKeluar::generateNomorSurat(
            $nomenklatur,
            $hierarchy['klasifikasi']->kode,
            $hierarchy['fungsi'] ? $hierarchy['fungsi']->kode : null,
            $hierarchy['kegiatan'] ? $hierarchy['kegiatan']->kode : null,
            $hierarchy['transaksi'] ? $hierarchy['transaksi']->kode : null,
            $issueDate->year,
            $issueDate->month
        );

        return $result['nomor'];
    }

    public function syncSuratKeluar(Rapat $rapat, $markComplete = false)
    {
        $rapat->loadMissing(['pesertas', 'kategoriSuratKode.parent.parent.parent', 'creator', 'approver1.jabatan', 'approver2.jabatan']);

        $selectedCategory = $rapat->kategoriSuratKode;
        if (!$selectedCategory) {
            throw new \RuntimeException('Kategori surat rapat belum dipilih.');
        }

        $hierarchy = $this->resolveHierarchy($selectedCategory);
        if (!$hierarchy['klasifikasi'] || !preg_match('/[A-Za-z]/', (string) $hierarchy['klasifikasi']->kode)) {
            throw new \RuntimeException('Hierarki kategori surat rapat tidak valid.');
        }

        $issueDate = $rapat->created_at
            ? $rapat->created_at->copy()->timezone('Asia/Jayapura')
            : Carbon::now('Asia/Jayapura');

        $nomenklatur = $this->resolveRapatNomenklatur($rapat);
        $suratKeluar = $rapat->suratKeluar;

        $result = SuratKeluar::generateNomorSurat(
            $nomenklatur,
            $hierarchy['klasifikasi']->kode,
            $hierarchy['fungsi'] ? $hierarchy['fungsi']->kode : null,
            $hierarchy['kegiatan'] ? $hierarchy['kegiatan']->kode : null,
            $hierarchy['transaksi'] ? $hierarchy['transaksi']->kode : null,
            $issueDate->year,
            $issueDate->month,
            $suratKeluar
                && (int) $suratKeluar->tahun_surat === (int) $issueDate->year
                && (string) $suratKeluar->nomenklatur_jabatan === (string) $nomenklatur
                ? $suratKeluar->nomor_urut
                : null
        );

        $payload = [
            'nomor_surat' => $result['nomor'],
            'nomor_urut' => $result['urut'],
            'tahun_surat' => $issueDate->year,
            'klasifikasi_kode_id' => $hierarchy['klasifikasi']->id,
            'kategori_surat_id' => optional($this->findKategoriSuratByHierarchy($hierarchy))->id,
            'kode_fungsi_id' => $hierarchy['fungsi'] ? $hierarchy['fungsi']->id : null,
            'kode_kegiatan_id' => $hierarchy['kegiatan'] ? $hierarchy['kegiatan']->id : null,
            'kode_transaksi_id' => $hierarchy['transaksi'] ? $hierarchy['transaksi']->id : null,
            'nomenklatur_jabatan' => $nomenklatur,
            'opsi_penerima' => 'internal',
            'penerima_external' => null,
            'perihal' => $rapat->judul,
            'tanggal_surat' => $issueDate->toDateString(),
            'has_lampiran' => true,
            'status' => $markComplete ? 'lengkap' : 'draft',
            'created_by' => $rapat->created_by,
            'rapat_id' => $rapat->id,
        ];

        if ($suratKeluar) {
            $suratKeluar->update($payload);
        } else {
            $suratKeluar = SuratKeluar::create($payload);
        }

        $suratKeluar->penerimaInternal()->sync($rapat->pesertas->pluck('id')->all());
        $rapat->forceFill(['nomor_undangan' => $suratKeluar->nomor_surat])->save();

        return $suratKeluar->fresh(['creator', 'penerimaInternal', 'klasifikasiKode', 'kodeFungsi', 'kodeKegiatan', 'kodeTransaksi']);
    }

    public function generateAndStoreUndangan(Rapat $rapat, $signed = false)
    {
        $rapat->loadMissing([
            'pesertas.jabatan',
            'pesertas.unit',
            'approvals',
            'approver1.jabatan',
            'approver2.jabatan',
            'creator',
            'kategoriSuratKode.parent.parent.parent',
            'suratKeluar',
        ]);

        $suratKeluar = $this->syncSuratKeluar($rapat, $signed);
        $verification = $this->pdfVerificationService->begin(
            'rapat',
            'undangan_rapat',
            $rapat->id,
            'Undangan Rapat - ' . ($rapat->judul ?: 'Rapat'),
            $this->buildRapatSigners($rapat),
            ['nomor' => $suratKeluar->nomor_surat ?: $rapat->nomor_undangan]
        );

        $content = $this->buildUndanganPdfContent($rapat, $signed, $verification);
        $this->pdfVerificationService->finalize($verification, $content, 'undangan-rapat-' . $rapat->id . '.pdf');

        $suratKeluar->update([
            'status' => $signed ? 'lengkap' : 'draft',
        ]);

        return $suratKeluar->fresh();
    }

    public function streamUndanganPdf(Rapat $rapat)
    {
        $rapat->loadMissing([
            'pesertas.jabatan',
            'pesertas.unit',
            'approvals',
            'approver1.jabatan',
            'approver2.jabatan',
            'creator',
            'kategoriSuratKode.parent.parent.parent',
            'suratKeluar',
        ]);

        $signed = $this->shouldUseSignedDocument($rapat);
        $suratKeluar = $this->syncSuratKeluar($rapat, $signed);
        $verification = $this->pdfVerificationService->begin(
            'rapat',
            'undangan_rapat',
            $rapat->id,
            'Undangan Rapat - ' . ($rapat->judul ?: 'Rapat'),
            $this->buildRapatSigners($rapat),
            ['nomor' => $suratKeluar->nomor_surat ?: $rapat->nomor_undangan]
        );
        $filename = 'undangan-rapat-' . $rapat->id . '.pdf';
        $content = $this->buildUndanganPdfContent($rapat, $signed, $verification);

        return $this->pdfVerificationService->response($content, $verification, $filename);
    }

    public function createUndanganTempFile(Rapat $rapat, $prefix = 'undangan-rapat')
    {
        $rapat->loadMissing([
            'pesertas.jabatan',
            'pesertas.unit',
            'approvals',
            'approver1.jabatan',
            'approver2.jabatan',
            'creator',
            'kategoriSuratKode.parent.parent.parent',
            'suratKeluar',
        ]);

        $signed = $this->shouldUseSignedDocument($rapat);
        $suratKeluar = $this->syncSuratKeluar($rapat, $signed);
        $verification = $this->pdfVerificationService->begin(
            'rapat',
            'undangan_rapat',
            $rapat->id,
            'Undangan Rapat - ' . ($rapat->judul ?: 'Rapat'),
            $this->buildRapatSigners($rapat),
            ['nomor' => $suratKeluar->nomor_surat ?: $rapat->nomor_undangan]
        );
        $content = $this->buildUndanganPdfContent($rapat, $signed, $verification);
        $this->pdfVerificationService->finalize($verification, $content, 'undangan-rapat-' . $rapat->id . '.pdf');
        $path = $this->makeTempPdfPath($prefix . '-' . $rapat->id);
        File::put($path, $content);

        return [
            'path' => $path,
            'temporary' => true,
        ];
    }

    protected function buildUndanganPdfContent(Rapat $rapat, $signed, $verification)
    {
        $tempFiles = [];

        try {
            $basePdf = PDF::loadView('rapat.pdf.undangan', $this->buildPdfViewData($rapat, $signed, $this->pdfVerificationService->viewData($verification)))
                ->setPaper('a4', 'portrait');

            $baseTempPath = $this->makeTempPdfPath('undangan-base-' . $rapat->id);
            File::put($baseTempPath, $basePdf->output());
            $tempFiles[] = $baseTempPath;

            $finalPdfContent = File::get($baseTempPath);

            if ($rapat->lampiran_tambahan_path) {
                $lampiranAttachment = $this->prepareLampiranTambahanPdf($rapat);
                $lampiranPdfPath = $lampiranAttachment['path'];
                if (!empty($lampiranAttachment['temporary'])) {
                    $tempFiles[] = $lampiranPdfPath;
                }

                $mergedTempPath = $this->makeTempPdfPath('undangan-merged-' . $rapat->id);
                $this->mergePdfFiles([$baseTempPath, $lampiranPdfPath], $mergedTempPath);
                $tempFiles[] = $mergedTempPath;
                $finalPdfContent = File::get($mergedTempPath);
            }

            return $finalPdfContent;
        } finally {
            foreach ($tempFiles as $tempFile) {
                if ($tempFile && file_exists($tempFile)) {
                    @unlink($tempFile);
                }
            }
        }
    }

    public function ensureUndanganPdf(Rapat $rapat)
    {
        $rapat->loadMissing('suratKeluar', 'approvals');

        $signed = $this->shouldUseSignedDocument($rapat);
        return $this->syncSuratKeluar($rapat, $signed);
    }

    public function shouldUseSignedDocument(Rapat $rapat)
    {
        $rapat->loadMissing('approvals');

        if ($rapat->approvals->count() > 0) {
            return $rapat->approvals->every(function ($approval) {
                return $approval->status === 'approved';
            });
        }

        return in_array($rapat->status, ['disetujui', 'selesai'], true);
    }

    public function buildPdfViewData(Rapat $rapat, $signed = false, array $pdfVerification = null)
    {
        $rapat->loadMissing([
            'pesertas.jabatan',
            'approvals.approver.jabatan',
            'approver1.jabatan',
            'approver2.jabatan',
            'creator',
            'kategoriSuratKode.parent.parent.parent',
            'suratKeluar',
        ]);

        $signatory = $this->resolveDocumentSignatory($rapat);
        $approvalStep = $this->resolveSignatureApprovalRecord($rapat, $signatory);
        $selectedCategory = $rapat->kategoriSuratKode;
        $hierarchy = $selectedCategory ? $this->resolveHierarchy($selectedCategory) : null;

        $displayRecipients = $rapat->pesertas->filter(function ($user) use ($signatory) {
            return !$signatory || (int) $user->id !== (int) $signatory->id;
        })->values();

        $tujuanManual = trim((string) $rapat->tujuan_surat) !== '';
        $singleRecipient = $displayRecipients->count() === 1;
        $showLampiranDaftar = $displayRecipients->count() > 1;
        $hasSignatoryContext = $signatory || trim((string) $rapat->approval1_jabatan_manual) !== '';
        $showTembusan = $hasSignatoryContext
            && !$this->isKetuaOrWakilKetua($signatory)
            && !$this->isKetuaOrWakilTitle($rapat->approval1_jabatan_manual);
        $issueDate = $rapat->created_at
            ? $rapat->created_at->copy()->timezone('Asia/Jayapura')
            : Carbon::now('Asia/Jayapura');
        $hasLampiranTambahan = (bool) $rapat->lampiran_tambahan_path;
        $showLampiranPage = !$hasLampiranTambahan && $displayRecipients->count() > 1;
        $showRecipientListInLetter = !$tujuanManual && !$showLampiranPage && $displayRecipients->count() > 1;

        return [
            'rapat' => $rapat,
            'suratKeluar' => $rapat->suratKeluar,
            'selectedCategory' => $selectedCategory,
            'hierarchy' => $hierarchy,
            'displayRecipients' => $displayRecipients,
            'tujuanManual' => $tujuanManual,
            'singleRecipient' => $singleRecipient,
            'showLampiranDaftar' => $showLampiranDaftar,
            'showLampiranPage' => $showLampiranPage,
            'hasLampiranTambahan' => $hasLampiranTambahan,
            'showRecipientListInLetter' => $showRecipientListInLetter,
            'showTembusan' => $showTembusan,
            'issueDate' => $issueDate,
            'signatory' => $signatory,
            'signatureApprovedAt' => $approvalStep && $approvalStep->acted_at ? $approvalStep->acted_at->copy()->timezone('Asia/Jayapura') : null,
            'signatureImage' => $signed && $signatory && $approvalStep && $approvalStep->status === 'approved'
                ? $this->signaturePadService->toDataUri($approvalStep->signature_path)
                : null,
            'kopImage' => $this->resolveKopImage(),
            'lampiranLabel' => $this->resolveLampiranLabel($showLampiranPage, $rapat),
            'openingParagraph' => $this->buildOpeningParagraph($rapat),
            'signatoryTitle' => $this->resolveSignatoryTitle($signatory, $rapat->approval1_jabatan_manual),
            'pdfVerification' => $pdfVerification,
        ];
    }

    protected function buildRapatSigners(Rapat $rapat)
    {
        $rapat->loadMissing('approvals.approver.jabatan');

        return $rapat->approvals
            ->where('status', 'approved')
            ->map(function ($approval) {
                return [
                    'name' => optional($approval->approver)->name ?: $approval->approver_name_snapshot ?: '-',
                    'role' => $approval->stage_label ?: 'Approval Rapat',
                    'title' => optional(optional($approval->approver)->jabatan)->nama ?: '-',
                    'signed_at' => $approval->acted_at ? $approval->acted_at->copy()->timezone('Asia/Jayapura')->translatedFormat('d F Y H:i') . ' WIT' : '-',
                ];
            })
            ->values()
            ->all();
    }

    public function resolveDocumentSignatory(Rapat $rapat)
    {
        if ($rapat->approver1) {
            return $rapat->approver1;
        }

        if ($rapat->approver2) {
            return $rapat->approver2;
        }

        return null;
    }

    protected function resolveSignatureApprovalRecord(Rapat $rapat, $signatory = null)
    {
        if ($rapat->approver_1_id) {
            $record = $rapat->approvals->firstWhere('approver_id', $rapat->approver_1_id);
            if ($record) {
                return $record;
            }
        }

        if ($signatory) {
            $record = $rapat->approvals->firstWhere('approver_id', $signatory->id);
            if ($record) {
                return $record;
            }
        }

        return $rapat->approvals->sortByDesc('step_order')->first();
    }

    public function resolveRapatNomenklatur(Rapat $rapat)
    {
        if ($rapat->nomenklatur_jabatan) {
            return $this->normalizeNomenklatur($rapat->nomenklatur_jabatan);
        }

        return $this->resolveNomenklaturFromUser($this->resolveDocumentSignatory($rapat));
    }

    public function resolveNomenklaturFromUser($user)
    {
        if ($user && optional($user->jabatan)->kode) {
            $code = strtoupper((string) $user->jabatan->kode);
            if ($code === 'SEK') {
                return 'sekretaris';
            }
            if ($code === 'PAN') {
                return 'panitera';
            }
            if ($code === 'KPTA') {
                return 'ketua';
            }
            if ($code === 'WKPTA') {
                return 'wakil_ketua';
            }
        }

        if ($user) {
            $roleNames = $user->roles ? $user->roles->pluck('name')->all() : [];
            if (in_array('sekretaris', $roleNames, true)) {
                return 'sekretaris';
            }
            if (in_array('panitera', $roleNames, true)) {
                return 'panitera';
            }
            if (in_array('ketua', $roleNames, true)) {
                return 'ketua';
            }
            if (in_array('wakil_ketua', $roleNames, true)) {
                return 'wakil_ketua';
            }
        }

        return 'sekretaris';
    }

    protected function normalizeNomenklatur($nomenklatur)
    {
        return in_array($nomenklatur, ['ketua', 'wakil_ketua', 'sekretaris', 'panitera'], true)
            ? $nomenklatur
            : 'sekretaris';
    }

    public function resolveHierarchy(KlasifikasiKode $node = null)
    {
        $hierarchy = [
            'klasifikasi' => null,
            'fungsi' => null,
            'kegiatan' => null,
            'transaksi' => null,
        ];

        while ($node) {
            if (array_key_exists($node->tipe, $hierarchy)) {
                $hierarchy[$node->tipe] = $node;
            }
            $node = $node->parent;
        }

        return $hierarchy;
    }

    protected function findKategoriSuratByHierarchy(array $hierarchy)
    {
        if (empty($hierarchy['klasifikasi'])) {
            return null;
        }

        return KategoriSurat::whereRaw('UPPER(kode) = ?', [strtoupper($hierarchy['klasifikasi']->kode)])->first();
    }

    public function buildApprovalSignatureData(Rapat $rapat, $signed = null)
    {
        $rapat->loadMissing([
            'creator',
            'suratKeluar',
            'approvals.approver.jabatan',
            'approver1.jabatan',
            'approver2.jabatan',
        ]);

        $signed = is_null($signed) ? $this->shouldUseSignedDocument($rapat) : (bool) $signed;
        $signatory = $this->resolveDocumentSignatory($rapat);
        $approvalStep = $this->resolveSignatureApprovalRecord($rapat, $signatory);

        return [
            'line1' => $this->resolveSignatoryTitle($signatory, $rapat->approval1_jabatan_manual)['line1'],
            'line2' => $this->resolveSignatoryTitle($signatory, $rapat->approval1_jabatan_manual)['line2'],
            'name' => optional($signatory)->name ?: '-',
            'signed_at' => $approvalStep && $approvalStep->acted_at
                ? $approvalStep->acted_at->copy()->timezone('Asia/Jayapura')
                : null,
            'image' => $signed && $signatory && $approvalStep && $approvalStep->status === 'approved'
                ? $this->signaturePadService->toDataUri($approvalStep->signature_path)
                : null,
        ];
    }

    public function buildNotulensiSignatureData(RapatNotulensi $notulensi)
    {
        $notulensi->loadMissing(['rapat.suratKeluar', 'notulis.jabatan']);

        return [
            'line1' => 'Notulis,',
            'line2' => 'Pengadilan Tinggi Agama Papua Barat',
            'name' => optional($notulensi->notulis)->name ?: '-',
            'signed_at' => ($notulensi->submitted_at ?: $notulensi->updated_at)
                ? ($notulensi->submitted_at ?: $notulensi->updated_at)->copy()->timezone('Asia/Jayapura')
                : null,
            'image' => $this->signaturePadService->toDataUri($notulensi->notulis_signature_path),
        ];
    }

    public function buildNotulensiApprovalSignatureData(RapatNotulensi $notulensi, $signed = null)
    {
        $notulensi->loadMissing([
            'rapat.approver1.jabatan',
            'rapat.approver2.jabatan',
            'approval.approver.jabatan',
        ]);

        $approval = $notulensi->approval;
        $signatory = optional($approval)->approver ?: $this->resolveDocumentSignatory($notulensi->rapat);
        $signed = is_null($signed) ? ($approval && $approval->status === 'approved') : (bool) $signed;
        $title = $this->resolveSignatoryTitle($signatory, $notulensi->rapat->approval1_jabatan_manual);

        return [
            'line1' => $title['line1'],
            'line2' => $title['line2'],
            'name' => optional($signatory)->name ?: '-',
            'signed_at' => $approval && $approval->acted_at
                ? $approval->acted_at->copy()->timezone('Asia/Jayapura')
                : null,
            'image' => $signed && $signatory && $approval && $approval->status === 'approved'
                ? $this->signaturePadService->toDataUri($approval->signature_path)
                : null,
        ];
    }

    public function buildSignatureVerificationData(Rapat $rapat, $signatureType = 'approval', RapatNotulensi $notulensi = null)
    {
        $rapat->loadMissing([
            'creator',
            'approver1.jabatan',
            'approver2.jabatan',
            'approvals.approver.jabatan',
            'kategoriSuratKode',
            'suratKeluar',
            'notulensi.notulis.jabatan',
        ]);

        if ($signatureType === 'notulis' && $notulensi) {
            $notulensi->loadMissing('notulis.jabatan');

            return [
                'valid' => (bool) $notulensi->notulis_id && !$notulensi->tidak_membuat_notulen,
                'nomor' => optional($rapat->suratKeluar)->nomor_surat ?: ($rapat->nomor_undangan ?: '-'),
                'judul' => $notulensi->judul ?: ($rapat->judul ?: '-'),
                'status_label' => ucfirst(str_replace('_', ' ', (string) $notulensi->status)),
                'signatory_name' => optional($notulensi->notulis)->name ?: '-',
                'signatory_title' => 'Notulis',
                'signed_at' => ($notulensi->submitted_at ?: $notulensi->updated_at)
                    ? ($notulensi->submitted_at ?: $notulensi->updated_at)->copy()->timezone('Asia/Jayapura')->translatedFormat('d F Y H:i') . ' WIT'
                    : '-',
                'created_by' => optional($rapat->creator)->name ?: '-',
                'kategori' => optional($rapat->kategoriSuratKode)->kode
                    ? ($rapat->kategoriSuratKode->kode . ' - ' . $rapat->kategoriSuratKode->nama)
                    : '-',
                'token' => $rapat->token_qr ?: '-',
                'verification_url' => $this->signatureVerificationUrl($rapat, ['signature' => 'notulis', 'notulensi' => $notulensi->id]),
            ];
        }

        if ($signatureType === 'notulensi_approval' && $notulensi) {
            $notulensi->loadMissing('approval.approver.jabatan');

            $approval = $notulensi->approval;
            $signatory = optional($approval)->approver ?: $this->resolveDocumentSignatory($rapat);
            $valid = $approval && $approval->status === 'approved';

            return [
                'valid' => $valid,
                'nomor' => optional($rapat->suratKeluar)->nomor_surat ?: ($rapat->nomor_undangan ?: '-'),
                'judul' => $notulensi->judul ?: ($rapat->judul ?: '-'),
                'status_label' => $valid ? 'Disetujui / Final' : ucfirst(str_replace('_', ' ', (string) $notulensi->status)),
                'signatory_name' => optional($signatory)->name ?: '-',
                'signatory_title' => optional($signatory)->jabatan_keterangan ?: optional(optional($signatory)->jabatan)->nama ?: '-',
                'signed_at' => $approval && $approval->acted_at
                    ? $approval->acted_at->copy()->timezone('Asia/Jayapura')->translatedFormat('d F Y H:i') . ' WIT'
                    : '-',
                'created_by' => optional($rapat->creator)->name ?: '-',
                'kategori' => optional($rapat->kategoriSuratKode)->kode
                    ? ($rapat->kategoriSuratKode->kode . ' - ' . $rapat->kategoriSuratKode->nama)
                    : '-',
                'token' => $rapat->token_qr ?: '-',
                'verification_url' => $this->signatureVerificationUrl($rapat, ['signature' => 'notulensi_approval', 'notulensi' => $notulensi->id]),
            ];
        }

        $signatory = $this->resolveDocumentSignatory($rapat);
        $approvalRecord = $this->resolveSignatureApprovalRecord($rapat, $signatory);
        $valid = $this->shouldUseSignedDocument($rapat)
            && $signatory
            && $approvalRecord
            && $approvalRecord->status === 'approved';

        return [
            'valid' => $valid,
            'nomor' => optional($rapat->suratKeluar)->nomor_surat ?: ($rapat->nomor_undangan ?: '-'),
            'judul' => $rapat->judul ?: '-',
            'status_label' => $valid ? 'Disetujui / Final' : ucfirst(str_replace('_', ' ', (string) $rapat->status)),
            'signatory_name' => optional($signatory)->name ?: '-',
            'signatory_title' => optional($signatory)->jabatan_keterangan ?: optional(optional($signatory)->jabatan)->nama ?: '-',
            'signed_at' => $approvalRecord && $approvalRecord->acted_at
                ? $approvalRecord->acted_at->copy()->timezone('Asia/Jayapura')->translatedFormat('d F Y H:i') . ' WIT'
                : '-',
            'created_by' => optional($rapat->creator)->name ?: '-',
            'kategori' => optional($rapat->kategoriSuratKode)->kode
                ? ($rapat->kategoriSuratKode->kode . ' - ' . $rapat->kategoriSuratKode->nama)
                : '-',
            'token' => $rapat->token_qr ?: '-',
            'verification_url' => $this->signatureVerificationUrl($rapat, ['signature' => 'approval']),
        ];
    }

    public function signatureVerificationUrl(Rapat $rapat, array $params = [])
    {
        if (!$rapat->token_qr) {
            $rapat->forceFill(['token_qr' => (string) Str::uuid()])->save();
        }

        return route('rapat.signature.verify', array_merge(['token' => $rapat->token_qr], $params));
    }

    protected function resolveLampiranLabel($showLampiranDaftar, Rapat $rapat)
    {
        if ($rapat->lampiran_tambahan_path) {
            return 'Satu Berkas';
        }

        if ($showLampiranDaftar && $rapat->pesertas()->count() > 1) {
            return 'Satu Lembar';
        }

        return '-';
    }

    protected function buildOpeningParagraph(Rapat $rapat)
    {
        if ($rapat->detail_tambahan) {
            return 'Memohon kehadiran Bapak/Ibu/Saudara dalam ' . trim($rapat->detail_tambahan) . ', yang akan dilaksanakan pada:';
        }

        return 'Memohon kehadiran Bapak/Ibu/Saudara dalam ' . $rapat->judul . ', yang akan dilaksanakan pada:';
    }

    protected function isKetuaOrWakilKetua($user)
    {
        if (!$user) {
            return false;
        }

        $code = strtoupper((string) optional($user->jabatan)->kode);

        return in_array($code, ['KPTA', 'WKPTA'], true);
    }

    protected function isKetuaOrWakilTitle($title)
    {
        $normalized = strtoupper(trim((string) $title));

        if ($normalized === '') {
            return false;
        }

        return strpos($normalized, 'KETUA') !== false || strpos($normalized, 'WAKIL KETUA') !== false;
    }

    protected function fileToDataUri($path)
    {
        if (!$path || !file_exists($path)) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'image/jpeg';

        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
    }

    protected function resolveKopImage()
    {
        $candidates = [
            public_path('kop_undangan.png'),
            public_path('kop_undangan.jpeg'),
            public_path('kop_undangan.jpg'),
        ];

        foreach ($candidates as $candidate) {
            $data = $this->fileToDataUri($candidate);
            if ($data) {
                return $data;
            }
        }

        return null;
    }

    protected function resolveSignatoryTitle($signatory, $manualTitle = null)
    {
        $manualTitle = trim((string) $manualTitle);

        if ($manualTitle !== '') {
            return [
                'line1' => rtrim($manualTitle, ',') . ',',
                'line2' => 'Pengadilan Tinggi Agama Papua Barat',
            ];
        }

        if (!$signatory) {
            return [
                'line1' => 'Pejabat Penanda Tangan,',
                'line2' => 'Pengadilan Tinggi Agama Papua Barat',
            ];
        }

        $code = strtoupper((string) optional($signatory->jabatan)->kode);

        if ($code === 'KPTA') {
            return ['line1' => 'Ketua,', 'line2' => 'Pengadilan Tinggi Agama Papua Barat'];
        }

        if ($code === 'WKPTA') {
            return ['line1' => 'Wakil Ketua,', 'line2' => 'Pengadilan Tinggi Agama Papua Barat'];
        }

        if ($code === 'SEK') {
            return ['line1' => 'Sekretaris,', 'line2' => 'Pengadilan Tinggi Agama Papua Barat'];
        }

        if ($code === 'PAN') {
            return ['line1' => 'Panitera,', 'line2' => 'Pengadilan Tinggi Agama Papua Barat'];
        }

        return [
            'line1' => ($signatory->jabatan_keterangan ?: optional($signatory->jabatan)->nama ?: 'Pejabat Penanda Tangan') . ',',
            'line2' => 'Pengadilan Tinggi Agama Papua Barat',
        ];
    }

    protected function prepareLampiranTambahanPdf(Rapat $rapat)
    {
        $absolutePath = Storage::disk('public')->path($rapat->lampiran_tambahan_path);

        if (!file_exists($absolutePath)) {
            throw new \RuntimeException('File lampiran tambahan tidak ditemukan.');
        }

        $mime = strtolower((string) $rapat->lampiran_tambahan_mime);

        if ($mime === 'application/pdf' || strtolower((string) pathinfo($absolutePath, PATHINFO_EXTENSION)) === 'pdf') {
            return ['path' => $absolutePath, 'temporary' => false];
        }

        if (in_array($mime, ['image/jpeg', 'image/png', 'image/jpg'], true)) {
            return ['path' => $this->renderImageAttachmentAsPdf($absolutePath, $rapat), 'temporary' => true];
        }

        throw new \RuntimeException('Lampiran tambahan untuk undangan hanya mendukung PDF atau gambar JPG/PNG.');
    }

    protected function renderImageAttachmentAsPdf($imagePath, Rapat $rapat)
    {
        $imageData = $this->fileToDataUri($imagePath);
        if (!$imageData) {
            throw new \RuntimeException('Gagal membaca file gambar lampiran tambahan.');
        }

        $html = '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        @page { size: A4 portrait; margin: 1.5cm; }
        body { margin: 0; font-family: Arial, sans-serif; }
        .wrap { width: 100%; text-align: center; }
        .img { max-width: 100%; max-height: 25.7cm; }
    </style>
</head>
<body>
    <div class="wrap">
        <img class="img" src="' . $imageData . '" alt="' . e($rapat->lampiran_tambahan_nama ?: 'Lampiran Tambahan') . '">
    </div>
</body>
</html>';

        $tempPath = $this->makeTempPdfPath('lampiran-image-' . $rapat->id);
        $pdf = PDF::loadHTML($html)->setPaper('a4', 'portrait');
        File::put($tempPath, $pdf->output());

        return $tempPath;
    }

    protected function mergePdfFiles(array $sourceFiles, $outputPath)
    {
        $pdf = new Fpdi();

        foreach ($sourceFiles as $sourceFile) {
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
        $directory = storage_path('app/temp/rapat');
        if (!is_dir($directory)) {
            File::makeDirectory($directory, 0755, true, true);
        }

        return $directory . DIRECTORY_SEPARATOR . $prefix . '-' . Str::uuid() . '.pdf';
    }
}
