<?php

namespace App\Http\Controllers;

use App\KlasifikasiKode;
use App\Http\Requests\GenerateSuratTemplatePreviewRequest;
use App\Http\Requests\ProcessSuratTemplateProposalRequest;
use App\Http\Requests\StoreSuratTemplateProposalRequest;
use App\Http\Requests\StoreSuratTemplateRequest;
use App\Http\Requests\UpdateSuratTemplateRequest;
use App\KategoriSurat;
use App\SuratKeluar;
use App\SuratTemplate;
use App\SuratTemplateProposal;
use App\Support\SuratTemplateCatalog;
use App\Services\SuratTemplateDocumentService;
use App\Services\SuratKeluarApprovalService;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SuratTemplateController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $this->abortIfUnauthorized();

        $moduleReady = $this->templatesReady();
        $proposalModuleReady = $this->proposalsReady();
        $filters = $request->only(['search', 'status']);

        if ($moduleReady) {
            $query = SuratTemplate::query()->with(['creator', 'approver']);

            if (!$this->canManage()) {
                $query->where('status', 'active');
            }

            if ($request->filled('search')) {
                $search = trim($request->search);
                $query->where(function ($builder) use ($search) {
                    $builder->where('name', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $templates = $query->orderByRaw("CASE WHEN status = 'active' THEN 0 WHEN status = 'draft' THEN 1 ELSE 2 END")
                ->orderBy('name')
                ->get()
                ->map(function ($template) {
                    return $this->applySystemTemplateOverrides($template);
                });
        } else {
            $templates = collect($this->defaultTemplates())
                ->filter(function ($template) use ($request) {
                    if (!$request->filled('search')) {
                        return true;
                    }

                    $search = mb_strtolower(trim($request->search));
                    return str_contains(mb_strtolower($template['name']), $search)
                        || str_contains(mb_strtolower($template['category']), $search)
                        || str_contains(mb_strtolower($template['description']), $search);
                })
                ->values();
        }

        $proposals = collect();
        if ($proposalModuleReady) {
            $proposalQuery = SuratTemplateProposal::query()->with(['requester', 'resolver']);

            if (!$this->canManage()) {
                $proposalQuery->where('requested_by', auth()->id());
            }

            $proposals = $proposalQuery->latest()->get();
        }

        return view('surat-template.index', [
            'templates' => $templates,
            'proposals' => $proposals,
            'filters' => $filters,
            'moduleReady' => $moduleReady,
            'proposalModuleReady' => $proposalModuleReady,
            'canManageTemplates' => $this->canManage(),
            'canSubmitProposal' => auth()->user()->canSubmitSuratTemplateProposal(),
            'templateUsers' => User::with('jabatan')->active()->ordered()->get(),
            'templateSignerUsers' => User::with('jabatan')
                ->active()
                ->withRoleOrDelegatedJabatan(['approval'])
                ->ordered()
                ->get(),
        ]);
    }

    public function preview(GenerateSuratTemplatePreviewRequest $request, $slug)
    {
        $this->abortIfUnauthorized();

        $template = $this->findTemplateBySlug($slug);
        abort_unless($template, 404);

        $fieldSchema = $this->extractFieldSchema($template);
        $validatedFields = $this->prepareFieldValuesForTemplate(
            $template,
            $this->validateDynamicFields($request, $fieldSchema)
        );
        $templateBody = $this->extractTemplateBody($template);
        $renderedBody = $this->renderTemplateBody($template, $templateBody, $validatedFields);
        $prefill = $this->buildSuratKeluarPrefill($template, $validatedFields, $renderedBody);

        return view('surat-template.preview', [
            'template' => $template,
            'templateSlug' => data_get($template, 'slug'),
            'fieldSchema' => $fieldSchema,
            'fieldValues' => $validatedFields,
            'renderedBody' => $renderedBody,
            'prefill' => $prefill,
            'canManageSuratKeluar' => auth()->user()->canCreateSuratKeluar(),
        ]);
    }

    public function handoffToSuratKeluar(GenerateSuratTemplatePreviewRequest $request, $slug)
    {
        $this->abortIfUnauthorized();
        abort_unless(auth()->user()->canCreateSuratKeluar(), 403);

        $template = $this->findTemplateBySlug($slug);
        abort_unless($template, 404);

        $fieldSchema = $this->extractFieldSchema($template);
        $validatedFields = $this->prepareFieldValuesForTemplate(
            $template,
            $this->validateDynamicFields($request, $fieldSchema)
        );
        $renderedBody = $this->renderTemplateBody($template, $this->extractTemplateBody($template), $validatedFields);

        $prefill = $this->buildSuratKeluarPrefill($template, $validatedFields, $renderedBody);

        if ($this->isSuratTugasTemplate($template)) {
            $suratKeluar = $this->createSuratKeluarFromTemplate($prefill);

            return redirect()
                ->route('surat-keluar.index')
                ->with('success', 'Surat tugas berhasil dibuat dan masuk ke tindak lanjut approval dengan nomor ' . $suratKeluar->nomor_surat_formatted . '.');
        }

        return redirect()
            ->route('surat-keluar.index')
            ->with('surat_template_prefill', $prefill)
            ->with('success', 'Draft surat keluar dari template siap dilengkapi.');
    }

    public function store(StoreSuratTemplateRequest $request)
    {
        $this->abortIfUnauthorized(true);
        $this->ensureTemplatesReady();
        $this->ensureUniqueTemplateSlug($request->slug);

        $data = $this->extractTemplatePayload($request);
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();
        if ($data['status'] === 'active') {
            $data['approved_by'] = auth()->id();
            $data['approved_at'] = now();
        }

        if ($request->hasFile('sample_file')) {
            $data['sample_file_path'] = $request->file('sample_file')->store('surat-template/samples', 'public');
        }

        SuratTemplate::create($data);

        return redirect()->route('surat-template.index')->with('success', 'Template surat berhasil ditambahkan.');
    }

    public function update(UpdateSuratTemplateRequest $request, SuratTemplate $suratTemplate)
    {
        $this->abortIfUnauthorized(true);
        $this->ensureTemplatesReady();
        $this->ensureUniqueTemplateSlug($request->slug, $suratTemplate->id);

        $data = $this->extractTemplatePayload($request);
        $data['updated_by'] = auth()->id();
        if ($data['status'] === 'active' && !$suratTemplate->approved_at) {
            $data['approved_by'] = auth()->id();
            $data['approved_at'] = now();
        }

        if ($request->hasFile('sample_file')) {
            if ($suratTemplate->sample_file_path) {
                Storage::disk('public')->delete($suratTemplate->sample_file_path);
            }
            $data['sample_file_path'] = $request->file('sample_file')->store('surat-template/samples', 'public');
        }

        $suratTemplate->update($data);

        return redirect()->route('surat-template.index')->with('success', 'Template surat berhasil diperbarui.');
    }

    public function storeProposal(StoreSuratTemplateProposalRequest $request)
    {
        $this->abortIfUnauthorized();
        abort_unless(auth()->user()->canSubmitSuratTemplateProposal(), 403);
        $this->ensureProposalsReady();

        $proposal = new SuratTemplateProposal();
        $proposal->fill([
            'title' => $request->title,
            'slug' => $request->slug,
            'category' => $request->category,
            'description' => $request->description,
            'requested_fields' => $this->normalizeRequestedFields($request->requested_fields),
            'suggested_template_body' => $request->suggested_template_body,
            'status' => 'submitted',
            'requested_by' => auth()->id(),
        ]);
        $proposal->example_file_path = $request->file('example_file')->store('surat-template/proposals', 'public');
        $proposal->save();

        return redirect()->route('surat-template.index')->with('success', 'Pengajuan template baru berhasil dikirim ke super admin.');
    }

    public function processProposal(ProcessSuratTemplateProposalRequest $request, SuratTemplateProposal $proposal)
    {
        $this->abortIfUnauthorized(true);
        $this->ensureTemplatesReady();
        $this->ensureProposalsReady();

        if ($request->action === 'reject') {
            $proposal->update([
                'status' => 'rejected',
                'review_notes' => $request->review_notes,
                'resolved_by' => auth()->id(),
                'resolved_at' => now(),
            ]);

            return redirect()->route('surat-template.index')->with('success', 'Pengajuan template ditolak.');
        }

        $this->ensureUniqueTemplateSlug($request->template_slug);

        $payload = [
            'name' => $request->template_name,
            'slug' => $request->template_slug,
            'category' => $request->template_category,
            'description' => $proposal->description,
            'status' => $request->template_status,
            'field_schema' => $this->decodeFieldSchema($request->field_schema),
            'template_body' => $request->template_body,
            'sample_file_path' => $proposal->example_file_path,
            'source_type' => 'proposal',
            'source_request_id' => $proposal->id,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ];

        SuratTemplate::create($payload);

        $proposal->update([
            'status' => 'approved',
            'review_notes' => $request->review_notes,
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
        ]);

        return redirect()->route('surat-template.index')->with('success', 'Pengajuan template berhasil ditindaklanjuti dan dijadikan template aktif.');
    }

    public function sample($type, $id)
    {
        $this->abortIfUnauthorized();

        if ($type === 'template') {
            $this->ensureTemplatesReady();
            $item = SuratTemplate::findOrFail($id);
            $path = $item->sample_file_path;
        } else {
            $this->ensureProposalsReady();
            $item = SuratTemplateProposal::findOrFail($id);
            $path = $item->example_file_path;
        }

        abort_unless($path && Storage::disk('public')->exists($path), 404);

        return response()->file(Storage::disk('public')->path($path));
    }

    protected function abortIfUnauthorized($manageOnly = false)
    {
        $user = auth()->user();
        abort_unless($user && ($manageOnly ? $user->canManageSuratTemplates() : $user->canAccessSuratTemplateMenu()), 403);
    }

    protected function canManage()
    {
        return auth()->check() && auth()->user()->canManageSuratTemplates();
    }

    protected function templatesReady()
    {
        return Schema::hasTable('surat_templates');
    }

    protected function proposalsReady()
    {
        return Schema::hasTable('surat_template_proposals');
    }

    protected function ensureTemplatesReady()
    {
        abort_unless($this->templatesReady(), 404);
    }

    protected function ensureProposalsReady()
    {
        abort_unless($this->proposalsReady(), 404);
    }

    protected function defaultTemplates()
    {
        return SuratTemplateCatalog::all();
    }

    protected function findTemplateBySlug($slug)
    {
        if ($this->templatesReady()) {
            $query = SuratTemplate::query()->where('slug', $slug);
            if (!$this->canManage()) {
                $query->where('status', 'active');
            }

            $template = $query->first();
            if ($template) {
                return $this->applySystemTemplateOverrides($template);
            }
        }

        return SuratTemplateCatalog::find($slug);
    }

    protected function extractFieldSchema($template)
    {
        return data_get($this->applySystemTemplateOverrides($template), 'field_schema', []);
    }

    protected function extractTemplateBody($template)
    {
        return (string) data_get($this->applySystemTemplateOverrides($template), 'template_body', '');
    }

    protected function validateDynamicFields(Request $request, array $fieldSchema)
    {
        $rules = [];
        foreach ($fieldSchema as $field) {
            $name = $field['name'] ?? null;
            if (!$name) {
                continue;
            }

            $baseRule = !empty($field['required']) ? 'required' : 'nullable';
            $type = $field['type'] ?? 'text';

            if ($type === 'date') {
                $rules['fields.' . $name] = $baseRule . '|date';
            } elseif ($type === 'user_multi') {
                $rules['fields.' . $name] = $baseRule . '|array|min:1';
                $rules['fields.' . $name . '.*'] = Rule::exists('users', 'id')->where('status_aktif_pegawai', true);
            } elseif ($type === 'user_select') {
                $rules['fields.' . $name] = [$baseRule, Rule::exists('users', 'id')->where('status_aktif_pegawai', true)];
            } else {
                $rules['fields.' . $name] = $baseRule . '|string|max:5000';
            }
        }

        $validated = $request->validate($rules);
        $fields = $validated['fields'] ?? [];

        if (!empty($fields['penanda_tangan_id'])) {
            $validSigner = User::withRoleOrDelegatedJabatan(['approval'])
                ->active()
                ->where('id', $fields['penanda_tangan_id'])
                ->exists();

            if (!$validSigner) {
                throw ValidationException::withMessages([
                    'fields.penanda_tangan_id' => ['Penanda tangan harus user dengan role approval.'],
                ]);
            }
        }

        return $fields;
    }

    protected function renderTemplateBody($template, $templateBody, array $fields)
    {
        if ($this->isSuratTugasTemplate($template)) {
            return view('surat-template.renderers.surat-tugas-preview', [
                'fieldValues' => $fields,
                'petugasRows' => $this->parsePetugasRows($fields['daftar_petugas'] ?? ''),
                'dasarRows' => $this->parseLineItems($fields['dasar_hukum'] ?? ''),
            ])->render();
        }

        $replacements = [];

        foreach ($fields as $key => $value) {
            $formatted = $value;
            if ($this->looksLikeDate($value)) {
                $formatted = Carbon::parse($value)->translatedFormat('d F Y');
            }

            $replacements['{{' . $key . '}}'] = e($formatted);
        }

        $rendered = strtr($templateBody, $replacements);

        return preg_replace('/\{\{[^}]+\}\}/', '-', $rendered);
    }

    protected function prepareFieldValuesForTemplate($template, array $fields)
    {
        if (!$this->isSuratTugasTemplate($template)) {
            return $fields;
        }

        $tanggal = now();
        $defaults = SuratTemplateCatalog::resolveSuratKeluarDefaults($template);
        $hierarchy = $this->resolveHierarchyForTemplateDefaults($defaults);

        $generated = SuratKeluar::generateNomorSurat(
            $defaults['nomenklatur_jabatan'] ?? 'ketua',
            $hierarchy['klasifikasi_kode'] ?? 'KP',
            $hierarchy['fungsi_kode'] ?? 'KP7',
            $hierarchy['kegiatan_kode'] ?? 'KP7.1',
            null,
            (int) $tanggal->format('Y'),
            (int) $tanggal->format('n')
        );

        $fields['tanggal_surat'] = $tanggal->format('Y-m-d');
        $fields['nomor_surat'] = $generated['nomor'];
        $fields['kota_tanda_tangan'] = 'Manokwari';
        $fields['lokasi'] = trim((string) ($fields['lokasi'] ?? '')) ?: '-';
        unset($fields['untuk_tugas']);
        $fields['dasar_hukum_default'] = $this->defaultSuratTugasDasarHukum();
        $fields['dasar_hukum_rows'] = array_merge(
            $fields['dasar_hukum_default'],
            $this->parseLineItems($fields['tambahan_dasar_hukum'] ?? '')
        );

        $selectedPetugas = User::with('jabatan')
            ->active()
            ->whereIn('id', $fields['petugas_ids'] ?? [])
            ->get()
            ->sortBy(function ($user) use ($fields) {
                return array_search((int) $user->id, array_map('intval', $fields['petugas_ids'] ?? []), true);
            })
            ->values();

        $fields['petugas_rows'] = $selectedPetugas->map(function ($user) {
            return [
                'id' => $user->id,
                'nama' => $user->name,
                'nip' => $user->nip ?: '-',
                'pangkat' => $user->jabatan_keterangan ?: '-',
                'jabatan' => optional($user->jabatan)->nama ?: ($user->jabatan_keterangan ?: '-'),
            ];
        })->all();

        $penandaTangan = !empty($fields['penanda_tangan_id'])
            ? User::with('jabatan')->active()->find($fields['penanda_tangan_id'])
            : null;

        $fields['penanda_tangan'] = [
            'id' => optional($penandaTangan)->id,
            'nama' => optional($penandaTangan)->name ?: 'Pejabat Penanda Tangan',
            'nip' => optional($penandaTangan)->nip ?: '-',
            'jabatan' => optional(optional($penandaTangan)->jabatan)->nama ?: (optional($penandaTangan)->jabatan_keterangan ?: '-'),
            'jabatan_ttd' => trim((string) ($fields['jabatan_plh'] ?? '')) ?: (optional(optional($penandaTangan)->jabatan)->nama ?: (optional($penandaTangan)->jabatan_keterangan ?: 'Ketua')),
        ];

        return $fields;
    }

    protected function looksLikeDate($value)
    {
        if (!is_string($value) || trim($value) === '') {
            return false;
        }

        try {
            Carbon::parse($value);
            return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1;
        } catch (\Throwable $exception) {
            return false;
        }
    }

    protected function extractTemplatePayload(Request $request)
    {
        return [
            'name' => $request->name,
            'slug' => $request->slug,
            'category' => $request->category,
            'description' => $request->description,
            'status' => $request->status,
            'field_schema' => $this->decodeFieldSchema($request->field_schema),
            'template_body' => $request->template_body,
            'source_type' => 'manual',
        ];
    }

    protected function decodeFieldSchema($json)
    {
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            throw ValidationException::withMessages([
                'field_schema' => ['Format field schema harus JSON array yang valid.'],
            ]);
        }

        return array_values(array_filter($decoded, function ($item) {
            return is_array($item) && !empty($item['name']) && !empty($item['label']);
        }));
    }

    protected function normalizeRequestedFields($value)
    {
        $lines = preg_split('/\r\n|\r|\n/', (string) $value);

        return array_values(array_filter(array_map(function ($line) {
            return trim($line);
        }, $lines)));
    }

    protected function buildSuratKeluarPrefill($template, array $fields, $renderedBody)
    {
        $templateName = $template instanceof SuratTemplate ? $template->name : ($template['name'] ?? 'Template Surat');
        $templateCategory = $template instanceof SuratTemplate ? $template->category : ($template['category'] ?? null);
        $tanggal = $fields['tanggal_surat'] ?? now()->toDateString();
        $defaults = SuratTemplateCatalog::resolveSuratKeluarDefaults($template);
        $hierarchy = $this->resolveHierarchyForTemplateDefaults($defaults);
        $targetKategori = null;

        if (!empty($defaults['kategori_kode'])) {
            $targetKategori = KategoriSurat::query()
                ->whereRaw('UPPER(kode) = ?', [strtoupper($defaults['kategori_kode'])])
                ->first();
        }

        if (!$targetKategori && $templateCategory) {
            $targetKategori = KategoriSurat::query()
                ->where(function ($query) use ($templateCategory) {
                    $query->where('nama', 'like', '%' . $templateCategory . '%')
                        ->orWhere('kode', 'like', '%' . $templateCategory . '%');
                })
                ->orderBy('nama')
                ->first();
        }

        return [
            'source' => 'template_surat',
            'template_name' => $templateName,
            'template_slug' => $template instanceof SuratTemplate ? $template->slug : ($template['slug'] ?? null),
            'tahun_surat' => Carbon::parse($tanggal)->format('Y'),
            'tanggal_surat' => Carbon::parse($tanggal)->format('Y-m-d'),
            'kategori_surat_id' => optional($targetKategori)->id,
            'kategori_surat_label' => $targetKategori ? ($targetKategori->kode . ' - ' . $targetKategori->nama) : '-',
            'nomenklatur_jabatan' => $defaults['nomenklatur_jabatan'] ?? 'sekretaris',
            'nomenklatur_label' => strtoupper($defaults['nomenklatur_jabatan'] ?? 'sekretaris') === 'KETUA' ? 'KPTA' : ucfirst(str_replace('_', ' ', $defaults['nomenklatur_jabatan'] ?? 'sekretaris')),
            'opsi_penerima' => $defaults['opsi_penerima'] ?? 'internal',
            'klasifikasi_kode_id' => $hierarchy['klasifikasi_id'] ?? null,
            'kode_fungsi_id' => $hierarchy['fungsi_id'] ?? null,
            'kode_kegiatan_id' => $hierarchy['kegiatan_id'] ?? null,
            'perihal' => $this->buildPrefillPerihal($templateName, $fields),
            'rendered_body' => $renderedBody,
            'field_values' => $fields,
            'penerima_internal_ids' => array_values(array_map('intval', $fields['petugas_ids'] ?? [])),
        ];
    }

    protected function buildPrefillPerihal($templateName, array $fields)
    {
        if (!empty($fields['dalam_rangka'])) {
            $summary = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $fields['dalam_rangka'])));
            return $templateName . ' - ' . mb_substr($summary, 0, 120);
        }

        $preferredKeys = [
            'nama_petugas',
            'nama_pegawai',
            'nama_pelaksana',
            'tujuan_tugas',
            'keperluan',
            'isi_keterangan',
        ];

        foreach ($preferredKeys as $key) {
            if (!empty($fields[$key])) {
                return $templateName . ' - ' . $fields[$key];
            }
        }

        return $templateName;
    }

    protected function applySystemTemplateOverrides($template)
    {
        $slug = (string) data_get($template, 'slug');
        $catalogTemplate = SuratTemplateCatalog::find($slug);

        if (!$catalogTemplate) {
            return $template;
        }

        if ($template instanceof SuratTemplate && data_get($template, 'source_type') !== 'system') {
            return $template;
        }

        if ($template instanceof SuratTemplate) {
            $template->field_schema = $catalogTemplate['field_schema'] ?? $template->field_schema;
            $template->template_body = $catalogTemplate['template_body'] ?? $template->template_body;
            $template->category = $catalogTemplate['category'] ?? $template->category;
            $template->description = $catalogTemplate['description'] ?? $template->description;
        }

        return $template instanceof SuratTemplate ? $template : array_merge($template, $catalogTemplate);
    }

    protected function isSuratTugasTemplate($template)
    {
        return (string) data_get($template, 'slug') === 'surat-tugas';
    }

    protected function parseLineItems($value)
    {
        return array_values(array_filter(array_map(function ($line) {
            return trim((string) $line);
        }, preg_split('/\r\n|\r|\n/', (string) $value))));
    }

    protected function parsePetugasRows($value)
    {
        $rows = [];
        foreach (preg_split('/\r\n|\r|\n/', (string) $value) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $parts = array_map('trim', explode('|', $line));
            $rows[] = [
                'nama' => $parts[0] ?? '-',
                'nip' => $parts[1] ?? '-',
                'pangkat' => $parts[2] ?? '-',
                'jabatan' => $parts[3] ?? '-',
            ];
        }

        return $rows;
    }

    protected function defaultSuratTugasDasarHukum()
    {
        return [
            'Undang-Undang Nomor 17 tahun 2003 tentang keuangan Negara;',
            'Undang-Undang Nomor 25 Tahun 2004 tentang Sistem Perencanaan Kerja dan Anggaran Kementerian Negara/Lembaga;',
            'PP Nomor 90 Tahun 2010 tentang Penyusunan Rencana Kerja dan Anggaran Kementerian Negara/Lembaga;',
            'Undang-Undang Nomor 5 Tahun 2014 Tentang Aparatur Sipil Negara.',
            'PMK Nomor 113 tahun 2012 tentang Perjalanan Dinas Dalam Negeri bagi Pejabat Negara, Pegawai Negeri dan Pegawai Tidak Tetap.',
            'PMK No. 39 Tahun 2024 Tentang Standar Biaya Masukan (SBM) Tahun Anggaran 2025',
        ];
    }

    protected function createSuratKeluarFromTemplate(array $prefill)
    {
        $suratKeluar = SuratKeluar::create([
            'nomor_surat' => $prefill['field_values']['nomor_surat'],
            'nomor_urut' => (int) explode('/', (string) $prefill['field_values']['nomor_surat'])[0],
            'tahun_surat' => $prefill['tahun_surat'],
            'klasifikasi_kode_id' => $prefill['klasifikasi_kode_id'],
            'kategori_surat_id' => $prefill['kategori_surat_id'],
            'kode_fungsi_id' => $prefill['kode_fungsi_id'],
            'kode_kegiatan_id' => $prefill['kode_kegiatan_id'],
            'kode_transaksi_id' => null,
            'nomenklatur_jabatan' => $prefill['nomenklatur_jabatan'],
            'opsi_penerima' => $prefill['opsi_penerima'],
            'penerima_external' => null,
            'perihal' => $prefill['perihal'],
            'tanggal_surat' => $prefill['tanggal_surat'],
            'has_lampiran' => false,
            'status' => 'draft',
            'created_by' => auth()->id(),
        ]);

        if (!empty($prefill['penerima_internal_ids'])) {
            $suratKeluar->penerimaInternal()->sync($prefill['penerima_internal_ids']);
        }

        app(SuratKeluarApprovalService::class)->syncForTemplate(
            $suratKeluar,
            [
                'template_name' => $prefill['template_name'],
                'template_slug' => $prefill['template_slug'],
                'rendered_body' => $prefill['rendered_body'],
                'field_values' => $prefill['field_values'],
            ],
            User::findOrFail($prefill['field_values']['penanda_tangan']['id']),
            auth()->user()
        );

        return $suratKeluar;
    }

    protected function resolveHierarchyForTemplateDefaults(array $defaults)
    {
        $kode = strtoupper(trim((string) ($defaults['kategori_kode'] ?? '')));
        if ($kode !== 'KP7.1') {
            return [];
        }

        $klasifikasi = KlasifikasiKode::where('tipe', 'klasifikasi')
            ->whereRaw('UPPER(kode) = ?', ['KP'])
            ->first();

        $fungsi = $klasifikasi
            ? KlasifikasiKode::where('tipe', 'fungsi')->where('parent_id', $klasifikasi->id)->whereRaw('UPPER(kode) = ?', ['KP7'])->first()
            : null;

        $kegiatan = $fungsi
            ? KlasifikasiKode::where('tipe', 'kegiatan')->where('parent_id', $fungsi->id)->whereRaw('UPPER(kode) = ?', ['KP7.1'])->first()
            : null;

        return [
            'klasifikasi_id' => optional($klasifikasi)->id,
            'fungsi_id' => optional($fungsi)->id,
            'kegiatan_id' => optional($kegiatan)->id,
            'klasifikasi_kode' => optional($klasifikasi)->kode ?: 'KP',
            'fungsi_kode' => optional($fungsi)->kode ?: 'KP7',
            'kegiatan_kode' => optional($kegiatan)->kode ?: 'KP7.1',
        ];
    }

    protected function ensureUniqueTemplateSlug($slug, $ignoreId = null)
    {
        $query = SuratTemplate::query()->where('slug', $slug);
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'slug' => ['Slug template sudah dipakai. Gunakan slug lain yang unik.'],
                'template_slug' => ['Slug template sudah dipakai. Gunakan slug lain yang unik.'],
            ]);
        }
    }
}

