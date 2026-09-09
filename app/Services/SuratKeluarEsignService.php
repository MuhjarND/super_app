<?php

namespace App\Services;

use App\PdfVerification;
use App\SuratKeluar;
use App\SuratKeluarApproval;
use App\Support\DocumentFilename;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;

class SuratKeluarEsignService
{
    public const TEMPLATE_SLUG = 'uploaded-pdf-esign';
    public const DOCUMENT_TYPE = 'surat_keluar_esign';

    /**
     * Nama berkas TTE yang tersedia di public/tte.
     *
     * Pemetaan dipusatkan di satu tempat agar daftar penandatangan, preview,
     * dan hasil akhir PDF selalu menggunakan aset yang sama.
     */
    protected const TTE_FILENAMES = [
        'ketua' => 'KETUA.png',
        'wakil_ketua' => 'WAKIL.png',
        'panitera' => 'PANITERA.png',
        'plt_sekretaris' => 'PLT_SEKRETARIS.png',
    ];

    protected $pdfVerificationService;

    public function __construct(PdfVerificationService $pdfVerificationService)
    {
        $this->pdfVerificationService = $pdfVerificationService;
    }

    /**
     * Daftar pegawai yang dapat dipilih berdasarkan berkas TTE yang tersedia.
     * File TTE sengaja dipetakan secara eksplisit agar pengguna tidak dapat
     * memilih file tanda tangan lain di luar folder public/tte.
     */
    public function availableTteSigners()
    {
        $files = [
            'ketua' => [
                'label' => 'Ketua',
                'filename' => self::TTE_FILENAMES['ketua'],
                'jabatan_codes' => ['KPTA'],
                'roles' => ['ketua'],
            ],
            'wakil_ketua' => [
                'label' => 'Wakil Ketua',
                'filename' => self::TTE_FILENAMES['wakil_ketua'],
                'jabatan_codes' => ['WKPTA'],
                'roles' => ['wakil_ketua'],
            ],
            'panitera' => [
                'label' => 'Panitera',
                'filename' => self::TTE_FILENAMES['panitera'],
                'jabatan_codes' => ['PAN'],
                'roles' => ['panitera'],
            ],
            'plt_sekretaris' => [
                'label' => 'PLT. Sekretaris',
                'filename' => self::TTE_FILENAMES['plt_sekretaris'],
                'jabatan_codes' => ['SEK'],
                'roles' => ['sekretaris'],
            ],
        ];

        $users = \App\User::active()
            ->with(['jabatan', 'roles', 'activeJabatanDelegations.jabatan'])
            ->ordered()
            ->get();

        return collect($files)->map(function (array $definition, $key) use ($users) {
            $path = $this->ttePath($key);
            if (!is_file($path)) {
                return null;
            }

            $user = $users
                ->filter(function ($candidate) use ($definition, $key) {
                    $hasRole = collect($definition['roles'])->contains(function ($role) use ($candidate) {
                        return $candidate->hasRole($role);
                    });
                    if ($hasRole) {
                        return true;
                    }

                    $hasJabatanCode = $candidate->effectiveJabatans()->contains(function ($jabatan) use ($definition) {
                        return $jabatan && in_array($jabatan->kode, $definition['jabatan_codes'], true);
                    });
                    if ($hasJabatanCode) {
                        return true;
                    }

                    // Some deployments record a PLT assignment in the free
                    // text jabatan field rather than using kode SEK.
                    if ($key === 'plt_sekretaris') {
                        $text = strtolower(trim(($candidate->jabatan_keterangan ?: '') . ' ' . optional($candidate->jabatan)->nama));
                        return strpos($text, 'sekretaris') !== false
                            && (strpos($text, 'plt') !== false || strpos($text, 'pelaksana tugas') !== false);
                    }

                    return false;
                })
                // Prefer a role explicitly assigned to the office over a
                // generic account that only happens to carry the jabatan code.
                ->sortByDesc(function ($candidate) use ($definition) {
                    return collect($definition['roles'])->contains(function ($role) use ($candidate) {
                        return $candidate->hasRole($role);
                    }) ? 1 : 0;
                })
                ->first();

            if (!$user) {
                return null;
            }

            return [
                'key' => $key,
                'label' => $definition['label'],
                'filename' => $definition['filename'],
                'path' => 'tte/' . $definition['filename'],
                'url' => asset('tte/' . rawurlencode($definition['filename'])),
                'user_id' => (int) $user->id,
                'name' => $user->name,
                'title' => $user->jabatan_keterangan ?: optional($user->jabatan)->nama ?: $definition['label'],
                'nip' => $user->nip ?: '-',
            ];
        })->filter()->values();
    }

    public function tteDefinition($key)
    {
        if (!isset(self::TTE_FILENAMES[$key])) {
            return null;
        }

        $filename = self::TTE_FILENAMES[$key];
        $path = $this->ttePath($key);
        return is_file($path) ? ['key' => $key, 'filename' => $filename, 'path' => $path] : null;
    }

    protected function ttePath($key)
    {
        return public_path('tte/' . (self::TTE_FILENAMES[$key] ?? ''));
    }

    public function isEligible(SuratKeluar $suratKeluar)
    {
        return $suratKeluar->file_path
            && strtolower(pathinfo($suratKeluar->file_path, PATHINFO_EXTENSION)) === 'pdf'
            && Storage::disk('public')->exists($suratKeluar->file_path);
    }

    public function sourcePath(SuratKeluar $suratKeluar)
    {
        abort_unless($this->isEligible($suratKeluar), 422, 'E-sign hanya dapat diajukan untuk berkas surat keluar berformat PDF.');

        return Storage::disk('public')->path($suratKeluar->file_path);
    }

    public function pageCount(SuratKeluar $suratKeluar)
    {
        $pdf = new Fpdi();

        try {
            return (int) $pdf->setSourceFile($this->sourcePath($suratKeluar));
        } catch (\Throwable $e) {
            throw new \RuntimeException('PDF tidak dapat dibaca untuk proses e-sign. Pastikan PDF tidak terenkripsi atau rusak.', 0, $e);
        }
    }

    public function streamSource(SuratKeluar $suratKeluar)
    {
        return response()->file($this->sourcePath($suratKeluar), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . DocumentFilename::fromLetter(
                $suratKeluar->nomor_surat_formatted,
                $suratKeluar->perihal
            ) . '"',
        ]);
    }

    public function streamApprovalPreview(SuratKeluarApproval $approval)
    {
        $approval->loadMissing('suratKeluar');
        abort_unless($approval->template_slug === self::TEMPLATE_SLUG && $approval->suratKeluar, 404);

        $filename = DocumentFilename::fromLetter(
            $approval->suratKeluar->nomor_surat_formatted,
            $approval->suratKeluar->perihal
        );

        if ($approval->status === 'approved') {
            $verification = $this->finalVerification($approval);
            if ($verification && $verification->file_path && Storage::disk('public')->exists($verification->file_path)) {
                return response()->file(Storage::disk('public')->path($verification->file_path), [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="' . $filename . '"',
                ]);
            }
        }

        // Saat masih pending, tampilkan juga pratinjau dengan gambar TTE di
        // posisi yang diajukan agar penanda tangan dapat memeriksa hasilnya
        // sebelum memilih setujui atau revisi. Berkas asli tidak diubah.
        try {
            $sourcePath = $this->sourcePath($approval->suratKeluar);
            $placement = (array) data_get($approval->field_values, 'esign_placement', []);
            $tteKey = data_get($approval->field_values, 'tte.key');
            if (!$tteKey) {
                $tteKey = $this->availableTteSigners()
                    ->firstWhere('user_id', (int) $approval->approver_id)['key'] ?? null;
            }
            $tte = $this->tteDefinition($tteKey);

            if ($tte) {
                $content = $this->stampPdfContent($sourcePath, $placement, $tte['path']);

                return response($content, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="' . $filename . '"',
                ]);
            }
        } catch (\Throwable $e) {
            // Pratinjau tidak boleh menghalangi approval bila PDF sumber
            // belum dapat distempel; fallback ke berkas asli di bawah.
        }

        return $this->streamSource($approval->suratKeluar);
    }

    public function finalVerification(SuratKeluarApproval $approval)
    {
        return PdfVerification::where('module', 'surat_keluar')
            ->where('document_type', self::DOCUMENT_TYPE)
            ->where('document_id', (string) $approval->surat_keluar_id)
            ->whereNotNull('file_path')
            ->latest('id')
            ->get()
            ->first(function ($verification) use ($approval) {
                return (int) data_get($verification->metadata, 'approval_id') === (int) $approval->id;
            });
    }

    public function finalize(SuratKeluarApproval $approval)
    {
        $approval->loadMissing(['suratKeluar', 'approver.jabatan']);
        abort_unless($approval->template_slug === self::TEMPLATE_SLUG, 422, 'Jenis approval e-sign tidak sesuai.');
        abort_unless($approval->status === 'approved', 422, 'Dokumen belum disetujui penanda tangan.');

        $suratKeluar = $approval->suratKeluar;
        $sourcePath = $this->sourcePath($suratKeluar);
        $placement = (array) data_get($approval->field_values, 'esign_placement', []);
        $signedAt = optional($approval->acted_at)->copy()->timezone('Asia/Jayapura');
        $signer = [
            'name' => $approval->signer_name_snapshot ?: optional($approval->approver)->name ?: '-',
            'role' => $approval->signer_title_snapshot ?: optional(optional($approval->approver)->jabatan)->nama ?: '-',
            'signed_at' => $signedAt ? $signedAt->translatedFormat('d F Y H:i') . ' WIT' : now('Asia/Jayapura')->translatedFormat('d F Y H:i') . ' WIT',
        ];
        $tteKey = data_get($approval->field_values, 'tte.key');
        if (!$tteKey) {
            $legacySigner = $this->availableTteSigners()->firstWhere('user_id', (int) $approval->approver_id);
            $tteKey = $legacySigner['key'] ?? null;
        }
        $tte = $this->tteDefinition($tteKey);
        if (!$tte) {
            throw new \RuntimeException('Berkas TTE penanda tangan tidak tersedia di folder public/tte.');
        }

        $verification = null;
        try {
            $verification = $this->pdfVerificationService->begin(
                'surat_keluar',
                self::DOCUMENT_TYPE,
                $suratKeluar->id,
                'Surat Keluar E-Sign - ' . ($suratKeluar->nomor_surat_formatted ?: $suratKeluar->id),
                [$signer],
                [
                    'approval_id' => $approval->id,
                    'nomor' => $suratKeluar->nomor_surat_formatted,
                    'perihal' => $suratKeluar->perihal,
                    'source_sha256' => hash_file('sha256', $sourcePath),
                    'placement' => $placement,
                    'tte_key' => $tteKey,
                    'tte_filename' => $tte['filename'],
                ]
            );

            $content = $this->stampPdfContent($sourcePath, $placement, $tte['path']);
            $filename = DocumentFilename::fromLetter($suratKeluar->nomor_surat_formatted, $suratKeluar->perihal);

            return $this->pdfVerificationService->finalize($verification, $content, $filename);
        } catch (\Throwable $e) {
            if ($verification && $verification->file_path) {
                Storage::disk('public')->delete($verification->file_path);
            }
            if ($verification && $verification->exists) {
                $verification->delete();
            }
            throw $e;
        }
    }

    public function stampPdfContent($sourcePath, array $placement, $ttePath)
    {
        $page = max(1, (int) ($placement['page'] ?? 1));
        $xPercent = $this->boundedNumber($placement['x'] ?? 58, 0, 92);
        $yPercent = $this->boundedNumber($placement['y'] ?? 76, 0, 92);
        $widthPercent = $this->boundedNumber($placement['width'] ?? 38, 30, 60);
        $heightPercent = $this->boundedNumber($placement['height'] ?? 14, 12, 30);

        if (($xPercent + $widthPercent) > 100 || ($yPercent + $heightPercent) > 100) {
            throw new \InvalidArgumentException('Posisi TTE berada di luar halaman PDF.');
        }

        if (!is_file($ttePath)) {
            throw new \InvalidArgumentException('Berkas TTE tidak ditemukan.');
        }

        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($sourcePath);
        if ($page > $pageCount) {
            throw new \InvalidArgumentException('Halaman penempatan TTE tidak tersedia pada PDF.');
        }

        foreach (range(1, $pageCount) as $pageNumber) {
            $templateId = $pdf->importPage($pageNumber);
            $size = $pdf->getTemplateSize($templateId);
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);

            if ($pageNumber === $page) {
                $this->drawEsignBox(
                    $pdf,
                    $size['width'] * ($xPercent / 100),
                    $size['height'] * ($yPercent / 100),
                    $size['width'] * ($widthPercent / 100),
                    $size['height'] * ($heightPercent / 100),
                    $ttePath
                );
            }
        }

        return $pdf->Output('S');
    }

    protected function drawEsignBox(Fpdi $pdf, $x, $y, $width, $height, $ttePath)
    {
        $pdf->SetDrawColor(148, 163, 184);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect($x, $y, $width, $height, 'DF');

        $padding = max(1, min(2, $height * 0.06));
        [$imageWidth, $imageHeight] = getimagesize($ttePath) ?: [1, 1];
        $ratio = $imageWidth / max(1, $imageHeight);
        $availableWidth = max(1, $width - ($padding * 2));
        $availableHeight = max(1, $height - ($padding * 2));
        $drawWidth = min($availableWidth, $availableHeight * $ratio);
        $drawHeight = $drawWidth / max(0.01, $ratio);
        $drawX = $x + (($width - $drawWidth) / 2);
        $drawY = $y + (($height - $drawHeight) / 2);
        $pdf->Image($ttePath, $drawX, $drawY, $drawWidth, $drawHeight, 'PNG');
    }

    protected function boundedNumber($value, $minimum, $maximum)
    {
        $value = (float) $value;
        return max($minimum, min($maximum, $value));
    }
}
