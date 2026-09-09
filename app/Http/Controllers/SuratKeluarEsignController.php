<?php

namespace App\Http\Controllers;

use App\Services\SuratKeluarApprovalService;
use App\Services\SuratKeluarEsignService;
use App\SuratKeluar;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SuratKeluarEsignController extends Controller
{
    protected $esignService;
    protected $approvalService;

    public function __construct(SuratKeluarEsignService $esignService, SuratKeluarApprovalService $approvalService)
    {
        $this->middleware('auth');
        $this->esignService = $esignService;
        $this->approvalService = $approvalService;
    }

    public function create(SuratKeluar $suratKeluar)
    {
        $this->authorizeRequest($suratKeluar);

        try {
            $pageCount = $this->esignService->pageCount($suratKeluar);
        } catch (\RuntimeException $e) {
            return redirect()->route('surat-keluar.index')->with('error', $e->getMessage());
        }

        $approval = $suratKeluar->templateApproval()
            ->where('template_slug', SuratKeluarEsignService::TEMPLATE_SLUG)
            ->latest('id')
            ->first();
        $placement = (array) data_get(optional($approval)->field_values, 'esign_placement', [
            'page' => 1,
            'x' => 58,
            'y' => 76,
            'width' => 38,
            'height' => 14,
        ]);
        $tteSigners = $this->esignService->availableTteSigners();

        return view('surat-keluar.esign.create', compact('suratKeluar', 'approval', 'placement', 'pageCount', 'tteSigners'));
    }

    public function store(Request $request, SuratKeluar $suratKeluar)
    {
        $this->authorizeRequest($suratKeluar);
        $pageCount = $this->esignService->pageCount($suratKeluar);

        $validated = $request->validate([
            'tte_key' => ['required', Rule::in(['ketua', 'wakil_ketua', 'panitera', 'plt_sekretaris'])],
            'approver_id' => ['required', Rule::exists('users', 'id')->where('status_aktif_pegawai', true)],
            'page' => 'required|integer|min:1|max:' . $pageCount,
            'x' => 'required|numeric|min:0|max:92',
            'y' => 'required|numeric|min:0|max:92',
            'width' => 'required|numeric|min:30|max:60',
            'height' => 'required|numeric|min:12|max:30',
        ]);

        if (((float) $validated['x'] + (float) $validated['width']) > 100
            || ((float) $validated['y'] + (float) $validated['height']) > 100) {
            throw ValidationException::withMessages([
                'position' => ['Posisi TTE harus seluruhnya berada di dalam halaman PDF.'],
            ]);
        }

        $tteSigner = $this->esignService->availableTteSigners()->firstWhere('key', $validated['tte_key']);
        if (!$tteSigner || (int) $tteSigner['user_id'] !== (int) $validated['approver_id']) {
            throw ValidationException::withMessages([
                'tte_key' => ['Penanda tangan tidak tersedia atau tidak sesuai dengan berkas TTE yang dipilih.'],
            ]);
        }
        $approver = \App\User::with('jabatan')->find($tteSigner['user_id']);

        $existingOtherApproval = $suratKeluar->templateApproval()
            ->where(function ($query) {
                $query->whereNull('template_slug')
                    ->orWhere('template_slug', '!=', SuratKeluarEsignService::TEMPLATE_SLUG);
            })
            ->exists();
        if ($existingOtherApproval) {
            throw ValidationException::withMessages([
                'approver_id' => ['Surat ini sudah memakai alur approval template. E-sign tambahan hanya tersedia untuk PDF yang diunggah langsung.'],
            ]);
        }

        $placement = [
            'page' => (int) $validated['page'],
            'x' => round((float) $validated['x'], 3),
            'y' => round((float) $validated['y'], 3),
            'width' => round((float) $validated['width'], 3),
            'height' => round((float) $validated['height'], 3),
        ];

        $this->approvalService->syncForTemplate(
            $suratKeluar,
            [
                'template_slug' => SuratKeluarEsignService::TEMPLATE_SLUG,
                'template_name' => 'E-Sign PDF Surat Keluar',
                'rendered_body' => null,
                'field_values' => [
                    'esign_placement' => $placement,
                    'source_file_path' => $suratKeluar->file_path,
                    'tte' => [
                        'key' => $tteSigner['key'],
                        'filename' => $tteSigner['filename'],
                        'path' => $tteSigner['path'],
                        'label' => $tteSigner['label'],
                    ],
                    'penanda_tangan' => [
                        'id' => $approver->id,
                        'nama' => $approver->name,
                        'nip' => $approver->nip ?: '-',
                        'jabatan_ttd' => optional($approver->jabatan)->nama ?: ($approver->jabatan_keterangan ?: '-'),
                    ],
                ],
            ],
            $approver,
            auth()->user()
        );

        return redirect()->route('surat-keluar.esign.create', $suratKeluar)
            ->with('success', 'Permohonan e-sign berhasil diajukan kepada ' . $approver->name . '.');
    }

    public function source(SuratKeluar $suratKeluar)
    {
        $this->authorizeRequest($suratKeluar);

        return $this->esignService->streamSource($suratKeluar);
    }

    protected function authorizeRequest(SuratKeluar $suratKeluar)
    {
        abort_unless(auth()->user()->canModifySuratKeluar($suratKeluar), 403);
        abort_unless($this->esignService->isEligible($suratKeluar), 422, 'Ajukan e-sign hanya tersedia untuk berkas PDF yang diunggah pada surat keluar.');
    }
}
