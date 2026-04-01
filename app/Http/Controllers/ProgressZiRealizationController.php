<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithProgressZi;
use App\Services\ProgressZi\ZiEvidenceBundleService;
use App\Services\ProgressZi\ZiEvidenceSourceService;
use App\ZiActivity;
use App\ZiActivityRealization;
use App\ZiEvidence;
use App\ZiGuidelineSubPoint;
use App\ZiIndicator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProgressZiRealizationController extends Controller
{
    use InteractsWithProgressZi;

    protected $evidenceSourceService;
    protected $bundleService;

    public function __construct(ZiEvidenceSourceService $evidenceSourceService, ZiEvidenceBundleService $bundleService)
    {
        $this->middleware('auth');
        $this->evidenceSourceService = $evidenceSourceService;
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
        $request->validate([
            'mode' => 'required|in:manual,linked',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx|max:10240',
            'linked_source' => 'nullable|string',
            'indicator_ids' => 'nullable|array',
            'indicator_ids.*' => 'exists:zi_indicators,id',
        ]);

        $this->persistEvidence($ziRealization, $request);

        return redirect()->route('progress-zi.activities.index', [
            'period_id' => $ziRealization->activity->zi_period_id,
            'area_id' => $ziRealization->activity->zi_area_id,
        ])->with('success', 'Eviden berhasil ditambahkan.');
    }

    public function storeEvidenceFromActivity(ZiActivity $ziActivity, Request $request)
    {
        abort_unless(auth()->user()->canManageProgressZiActivity($ziActivity), 403);

        $request->validate([
            'mode' => 'required|in:manual,linked',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx|max:10240',
            'linked_source' => 'nullable|string',
        ]);

        $realization = $ziActivity->realizations()->latest('id')->first();
        if (!$realization) {
            $realization = $ziActivity->realizations()->create([
                'realization_date' => $ziActivity->target_start_date ?: now()->toDateString(),
                'implementation_summary' => $ziActivity->name,
                'result_summary' => $ziActivity->description,
                'source_type' => $request->mode === 'linked' ? 'rapat' : 'manual',
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
        }

        $this->persistEvidence($realization, $request);

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
            'mode' => 'required|in:manual,linked',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx|max:10240',
            'linked_source' => 'nullable|string',
        ]);

        abort_unless(
            $ziGuidelineSubPoint->point && (int) $ziGuidelineSubPoint->point->zi_area_id === (int) $request->zi_area_id,
            422,
            'Sub poin tidak sesuai dengan area yang dipilih.'
        );

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
                'name' => 'Pemenuhan Eviden - ' . $ziGuidelineSubPoint->title,
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
                'source_type' => $request->mode === 'linked' ? 'persuratan' : 'manual',
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
        }

        $this->persistEvidence($realization, $request);

        $activity->update([
            'status' => 'sedang_berjalan',
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('progress-zi.activities.index', [
            'period_id' => $activity->zi_period_id,
            'area_id' => $activity->zi_area_id,
        ])->with('success', 'Eviden berhasil ditambahkan.');
    }

    public function reviewIndicator(ZiIndicator $ziIndicator, Request $request)
    {
        $this->abortUnlessCanVerifyProgressZi();
        $request->validate([
            'status' => 'required|in:belum_terpenuhi,sebagian_terpenuhi,terpenuhi,diverifikasi,ditolak',
            'review_decision' => 'required|in:approved,revisi,rejected',
            'review_notes' => 'nullable|string',
        ]);

        $ziIndicator->update(['status' => $request->status, 'updated_by' => auth()->id()]);
        $ziIndicator->reviews()->create([
            'review_scope' => 'indicator',
            'status' => $request->review_decision,
            'review_notes' => $request->review_notes,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Review indikator berhasil disimpan.');
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
        $payload = [
            'title' => $request->title,
            'description' => $request->description,
            'uploaded_by' => auth()->id(),
        ];

        if ($request->mode === 'manual') {
            $request->validate(['file' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx|max:10240']);
            $file = $request->file('file');
            $path = $file->store('progress-zi/evidences', 'public');
            $payload = array_merge($payload, [
                'source_type' => 'manual',
                'evidence_type' => 'manual_upload',
                'status' => 'terupload',
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
            ]);
            if (!$payload['title']) {
                $payload['title'] = $file->getClientOriginalName();
            }
        } else {
            $request->validate(['linked_source' => 'required|string']);
            $linked = $this->evidenceSourceService->resolveLinkedSource($request->linked_source);
            $payload = array_merge($payload, $linked, [
                'status' => 'terhubung',
                'is_auto_linked' => true,
            ]);
            if (!$request->filled('title')) {
                $payload['title'] = $linked['title'];
            }
            if (!$request->filled('description')) {
                $payload['description'] = $linked['description'];
            }
        }

        return $ziRealization->evidences()->create($payload);
    }
}
