<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithProgressZi;
use App\Services\ProgressZi\ZiEvidenceBundleService;
use App\Rules\GoogleDriveUrl;
use App\ZiActivity;
use App\ZiActivityRealization;
use App\ZiArea;
use App\ZiEvidence;
use App\ZiGuidelineSubPoint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProgressZiRealizationController extends Controller
{
    use InteractsWithProgressZi;

    protected $bundleService;

    public function __construct(ZiEvidenceBundleService $bundleService)
    {
        $this->middleware('auth');
        $this->bundleService = $bundleService;
    }

    public function store(ZiActivity $ziActivity, Request $request)
    {
        abort_unless(auth()->user()->canManageProgressZiActivity($ziActivity), 403);
        $request->validate([
            'realization_date' => 'required|date',
            'implementation_summary' => 'required|string',
            'result_summary' => 'nullable|string',
            'obstacles' => 'nullable|string',
            'follow_up' => 'nullable|string',
            'source_type' => 'required|in:manual,persuratan,rapat,cuti',
        ]);

        $ziActivity->realizations()->create($request->only(['realization_date', 'implementation_summary', 'result_summary', 'obstacles', 'follow_up', 'source_type']) + [
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('progress-zi.activities.index', [
            'period_id' => $ziActivity->zi_period_id,
            'area_id' => $ziActivity->zi_area_id,
        ])->with('success', 'Realisasi kegiatan berhasil ditambahkan.');
    }

    public function update(ZiActivityRealization $ziRealization, Request $request)
    {
        abort_unless(auth()->user()->canManageProgressZiActivity($ziRealization->activity()->with('area')->first()), 403);
        $request->validate([
            'realization_date' => 'required|date',
            'implementation_summary' => 'required|string',
            'result_summary' => 'nullable|string',
            'obstacles' => 'nullable|string',
            'follow_up' => 'nullable|string',
            'source_type' => 'required|in:manual,persuratan,rapat,cuti',
        ]);

        $ziRealization->update($request->only(['realization_date', 'implementation_summary', 'result_summary', 'obstacles', 'follow_up', 'source_type']) + [
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('progress-zi.activities.index', [
            'period_id' => $ziRealization->activity->zi_period_id,
            'area_id' => $ziRealization->activity->zi_area_id,
        ])->with('success', 'Realisasi kegiatan berhasil diperbarui.');
    }

    public function storeEvidence(ZiActivityRealization $ziRealization, Request $request)
    {
        abort_unless(auth()->user()->canManageProgressZiActivity($ziRealization->activity()->with('area')->first()), 403);
        $this->validateEvidence($request);

        $this->persistEvidence($ziRealization, $request);

        return redirect()->route('progress-zi.activities.index', [
            'period_id' => $ziRealization->activity->zi_period_id,
            'area_id' => $ziRealization->activity->zi_area_id,
        ])->with('success', 'Eviden berhasil ditambahkan.');
    }

    public function storeEvidenceFromActivity(ZiActivity $ziActivity, Request $request)
    {
        abort_unless(auth()->user()->canManageProgressZiActivity($ziActivity), 403);

        $this->validateEvidence($request);

        DB::transaction(function () use ($ziActivity, $request) {
            $realization = $ziActivity->realizations()->latest('id')->first();
            if (!$realization) {
                $realization = $ziActivity->realizations()->create([
                    'realization_date' => $ziActivity->target_start_date ?: now()->toDateString(),
                    'implementation_summary' => $ziActivity->name,
                    'result_summary' => $ziActivity->description,
                    'source_type' => 'manual',
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
            }

            $this->persistEvidence($realization, $request);
        });

        return redirect()->route('progress-zi.activities.index', [
            'period_id' => $ziActivity->zi_period_id,
            'area_id' => $ziActivity->zi_area_id,
        ])->with('success', 'Eviden berhasil ditambahkan.');
    }

    public function storeEvidenceFromSubPoint(ZiGuidelineSubPoint $ziGuidelineSubPoint, Request $request)
    {
        $request->validate([
            'zi_period_id' => 'required|exists:zi_periods,id',
            'zi_area_id' => 'required|exists:zi_areas,id',
        ]);
        $this->validateEvidence($request);
        $area = ZiArea::findOrFail($request->zi_area_id);

        abort_unless(
            $ziGuidelineSubPoint->point && (int) $ziGuidelineSubPoint->point->zi_area_id === (int) $request->zi_area_id,
            422,
            'Sub poin tidak sesuai dengan area yang dipilih.'
        );

        abort_unless(auth()->user()->canManageProgressZiArea($area), 403);

        $activity = DB::transaction(function () use ($ziGuidelineSubPoint, $request) {
            $activity = ZiActivity::where('zi_period_id', $request->zi_period_id)
                ->where('zi_area_id', $request->zi_area_id)
                ->where('zi_guideline_sub_point_id', $ziGuidelineSubPoint->id)
                ->latest('id')
                ->first();

            if (!$activity) {
                $activity = ZiActivity::create([
                    'zi_period_id' => $request->zi_period_id,
                    'zi_area_id' => $request->zi_area_id,
                    'zi_guideline_sub_point_id' => $ziGuidelineSubPoint->id,
                    'name' => Str::limit('Pemenuhan Eviden - ' . $ziGuidelineSubPoint->title, 252),
                    'description' => 'Pengumpulan eviden langsung untuk sub poin pedoman Zona Integritas tanpa pelaksanaan rapat monitoring.',
                    'target_start_date' => now()->toDateString(),
                    'target_end_date' => now()->toDateString(),
                    'pic_user_id' => auth()->id(),
                    'status' => 'belum_mulai',
                    'source_type' => 'manual',
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
            }

            abort_unless(auth()->user()->canManageProgressZiActivity($activity), 403);

            $realization = $activity->realizations()->latest('id')->first();
            if (!$realization) {
                $realization = $activity->realizations()->create([
                    'realization_date' => now()->toDateString(),
                    'implementation_summary' => 'Pengumpulan eviden untuk sub poin ' . strtolower($ziGuidelineSubPoint->code) . '.',
                    'result_summary' => $ziGuidelineSubPoint->title,
                    'source_type' => 'manual',
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
            }

            $this->persistEvidence($realization, $request);

            $activity->update([
                'status' => 'sedang_berjalan',
                'updated_by' => auth()->id(),
            ]);
            return $activity;
        });

        return redirect()->route('progress-zi.activities.index', [
            'period_id' => $activity->zi_period_id,
            'area_id' => $activity->zi_area_id,
        ])->with('success', 'Eviden berhasil ditambahkan.');
    }


    public function reviewEvidence(ZiEvidence $ziEvidence, Request $request)
    {
        $this->abortUnlessCanVerifyProgressZi();
        $request->validate([
            'status' => 'required|in:valid,revisi,tidak_valid',
            'review_decision' => 'required|in:approved,revisi,rejected',
            'review_notes' => 'nullable|string',
        ]);

        $ziEvidence->update(['status' => $request->status]);
        $ziEvidence->reviews()->create([
            'review_scope' => 'evidence',
            'status' => $request->review_decision,
            'review_notes' => $request->review_notes,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Review eviden berhasil disimpan.');
    }

    public function file(ZiEvidence $ziEvidence)
    {
        $this->abortUnlessCanAccessProgressZi();
        abort_unless($ziEvidence->file_path, 404);
        abort_unless(Storage::disk('public')->exists($ziEvidence->file_path), 404);
        return Storage::disk('public')->response($ziEvidence->file_path, $ziEvidence->file_name);
    }

    public function bundle(ZiActivity $ziActivity)
    {
        $this->abortUnlessCanAccessProgressZi();
        abort_unless(
            auth()->user()->canManageProgressZiActivity($ziActivity) || auth()->user()->canVerifyProgressZi(),
            403
        );

        $bundle = $this->bundleService->createBundle($ziActivity);
        $response = response()->file($bundle['path'], [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $bundle['download_name'] . '"',
        ]);

        if (method_exists($response, 'deleteFileAfterSend')) {
            $response->deleteFileAfterSend(true);
        }

        return $response;
    }

    protected function persistEvidence(ZiActivityRealization $ziRealization, Request $request)
    {
        return DB::transaction(function () use ($ziRealization, $request) {
            $evidence = $ziRealization->evidences()->create([
                'title' => $request->title ?: 'Eviden Google Drive',
                'description' => $request->description,
                'uploaded_by' => auth()->id(),
                'source_type' => 'google_drive',
                'source_reference_type' => 'google_drive',
                'evidence_type' => 'google_drive',
                'drive_url' => $request->drive_url,
                'status' => 'terhubung',
                'is_auto_linked' => false,
            ]);
            return $evidence;
        });
    }

    protected function validateEvidence(Request $request)
    {
        $noAttachment = function ($attribute, $value, $fail) {
            if ($value !== null) {
                $fail('Eviden baru dikumpulkan melalui tautan Google Drive.');
            }
        };
        $request->validate([
            'mode' => 'nullable|in:google_drive',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'drive_url' => ['required', 'string', 'max:2048', new GoogleDriveUrl],
            'file' => ['nullable', $noAttachment],
            'linked_source' => ['nullable', $noAttachment],
            'indicator_ids' => ['nullable', function ($attribute, $value, $fail) {
                if ($value !== null) { $fail('Pengumpulan eviden mengikuti panduan tetap. Indikator manual tidak digunakan.'); }
            }],
        ], [
            'drive_url.required' => 'Tautan Google Drive wajib diisi.',
            'drive_url.max' => 'Tautan Google Drive maksimal 2048 karakter.',
            'mode.in' => 'Eviden baru dikumpulkan melalui tautan Google Drive.',
        ]);
    }
}
