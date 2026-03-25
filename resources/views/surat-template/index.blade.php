@extends('layouts.app')

@section('title', 'Template Surat')

@php
    use Illuminate\Support\Str;
@endphp

@section('content')
@include('admin._alerts')

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-1">Template Surat</h3>
        <p class="text-muted mb-0">Gunakan template surat resmi dan kelola pengajuan template baru dari kabag atau kasubag.</p>
    </div>
    <div class="d-flex" style="gap:8px;">
        @if($canManageTemplates && $moduleReady)
            <button class="btn app-create-btn" data-toggle="modal" data-target="#createTemplateModal"><i class="fas fa-plus"></i> Tambah Template</button>
        @endif
        @if($canSubmitProposal && $proposalModuleReady)
            <button class="btn btn-primary" data-toggle="modal" data-target="#createProposalModal"><i class="fas fa-file-upload"></i> Ajukan Template</button>
        @endif
    </div>
</div>

@if(!$moduleReady)
    <div class="alert alert-warning border-0 shadow-sm">
        Daftar template saat ini masih memakai katalog bawaan aplikasi. Penyimpanan template baru dan pengajuan template akan aktif penuh setelah migration modul `Template Surat` dijalankan manual.
    </div>
@endif

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 pb-0">
        <form method="GET" action="{{ route('surat-template.index') }}">
            <div class="row">
                <div class="col-md-8 form-group mb-md-0">
                    <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Cari nama template, kategori, atau deskripsi">
                </div>
                @if($moduleReady && $canManageTemplates)
                    <div class="col-md-2 form-group mb-md-0">
                        <select name="status" class="form-control">
                            <option value="">Semua Status</option>
                            <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>Aktif</option>
                            <option value="draft" {{ ($filters['status'] ?? '') === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                        </select>
                    </div>
                @endif
                <div class="{{ $moduleReady && $canManageTemplates ? 'col-md-2' : 'col-md-4' }} d-flex" style="gap:6px;">
                    <button type="submit" class="btn btn-primary btn-block">Filter</button>
                    <a href="{{ route('surat-template.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </div>
        </form>
    </div>
    <div class="card-body pt-3">
        <div class="row">
            @forelse($templates as $template)
                @php
                    $templateId = data_get($template, 'id');
                    $templateName = data_get($template, 'name');
                    $templateSlug = data_get($template, 'slug');
                    $templateCategory = data_get($template, 'category');
                    $templateDescription = data_get($template, 'description');
                    $templateStatus = data_get($template, 'status', 'active');
                    $templateStatusLabel = data_get($template, 'status_label', ucfirst($templateStatus));
                    $templateStatusClass = data_get($template, 'status_badge_class', $templateStatus === 'active' ? 'success' : 'secondary');
                    $fieldSchema = data_get($template, 'field_schema', []);
                    $templateBody = data_get($template, 'template_body', '');
                    $samplePath = data_get($template, 'sample_file_path');
                    $isStoredTemplate = $template instanceof \App\SuratTemplate;
                    $fieldSchemaJson = json_encode($fieldSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                @endphp
                <div class="col-lg-4 mb-3">
                    <div class="card h-100 border-0 shadow-sm template-card">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <div class="font-weight-700">{{ $templateName }}</div>
                                    <div class="text-muted small">{{ $templateCategory }}</div>
                                </div>
                                <span class="badge badge-{{ $templateStatusClass }}">{{ $templateStatusLabel }}</span>
                            </div>
                            <p class="text-muted small flex-grow-1 mb-3">{{ $templateDescription ?: 'Template surat siap pakai dengan field dinamis sesuai kebutuhan dokumen.' }}</p>
                            <div class="small text-muted mb-3">
                                <strong>Field:</strong>
                                @if(!empty($fieldSchema))
                                    {{ collect($fieldSchema)->pluck('label')->implode(', ') }}
                                @else
                                    -
                                @endif
                            </div>
                            <div class="d-flex flex-wrap" style="gap:8px;">
                                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#useTemplateModal{{ $templateId }}">
                                    <i class="fas fa-pen-alt"></i> {{ $templateSlug === 'surat-tugas' ? 'Buat Surat Tugas' : 'Gunakan Template' }}
                                </button>
                                @if($isStoredTemplate && $samplePath)
                                    <a href="{{ route('surat-template.sample', ['type' => 'template', 'id' => $templateId]) }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-file-alt"></i> Contoh
                                    </a>
                                @endif
                                @if($canManageTemplates && $isStoredTemplate && $moduleReady)
                                    <button type="button" class="btn btn-outline-primary btn-sm" data-toggle="modal" data-target="#editTemplateModal{{ $templateId }}">
                                        <i class="fas fa-cog"></i> Kelola
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="useTemplateModal{{ $templateId }}" tabindex="-1">
                    <div class="modal-dialog modal-lg"><div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Gunakan {{ $templateName }}</h5>
                            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                        </div>
                        <form method="POST" action="{{ $templateSlug === 'surat-tugas' ? route('surat-template.handoff', $templateSlug) : route('surat-template.preview', $templateSlug) }}">
                            @csrf
                            <div class="modal-body">
                                <div class="alert alert-light border small">
                                    {{ $templateSlug === 'surat-tugas' ? 'Isi field berikut untuk langsung membuat Surat Tugas dan menyimpannya ke Surat Keluar.' : 'Isi field berikut untuk membuat preview surat dari template yang dipilih.' }}
                                </div>
                                <div class="row">
                                    @foreach($fieldSchema as $field)
                                        @php
                                            $fieldName = $field['name'] ?? '';
                                            $fieldLabel = $field['label'] ?? $fieldName;
                                            $fieldType = $field['type'] ?? 'text';
                                            $required = !empty($field['required']);
                                        @endphp
                                        <div class="col-md-{{ $fieldType === 'textarea' ? '12' : '6' }} form-group">
                                            <label>{{ $fieldLabel }} @if($required)<span class="text-danger">*</span>@endif</label>
                                            @if($fieldType === 'textarea')
                                                <textarea name="fields[{{ $fieldName }}]" class="form-control" rows="3" {{ $required ? 'required' : '' }}></textarea>
                                            @elseif($fieldType === 'user_multi')
                                                <select name="fields[{{ $fieldName }}][]" class="form-control select2" multiple {{ $required ? 'required' : '' }} data-dropdown-parent="#useTemplateModal{{ $templateId }}">
                                                    @foreach($templateUsers as $user)
                                                        <option value="{{ $user->id }}">{{ $user->name }}{{ optional($user->jabatan)->nama ? ' - ' . optional($user->jabatan)->nama : '' }}</option>
                                                    @endforeach
                                                </select>
                                            @elseif($fieldType === 'user_select')
                                                <select name="fields[{{ $fieldName }}]" class="form-control" {{ $required ? 'required' : '' }}>
                                                    <option value="">-- Pilih --</option>
                                                    @foreach(($fieldName === 'penanda_tangan_id' ? $templateSignerUsers : $templateUsers) as $user)
                                                        <option value="{{ $user->id }}">{{ $user->name }}{{ optional($user->jabatan)->nama ? ' - ' . optional($user->jabatan)->nama : '' }}</option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <input type="{{ $fieldType === 'date' ? 'date' : 'text' }}" name="fields[{{ $fieldName }}]" class="form-control" {{ $required ? 'required' : '' }}>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary">{{ $templateSlug === 'surat-tugas' ? 'Buat Surat Tugas' : 'Buat Preview' }}</button>
                            </div>
                        </form>
                    </div></div>
                </div>

                @if($canManageTemplates && $isStoredTemplate && $moduleReady)
                    <div class="modal fade" id="editTemplateModal{{ $templateId }}" tabindex="-1">
                        <div class="modal-dialog modal-xl"><div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Kelola Template Surat</h5>
                                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                            </div>
                            <form method="POST" action="{{ route('surat-template.update', $template) }}" enctype="multipart/form-data">
                                @csrf @method('PUT')
                                <div class="modal-body">@include('surat-template.partials.template-form', ['item' => $template, 'fieldSchemaJson' => $fieldSchemaJson])</div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                </div>
                            </form>
                        </div></div>
                    </div>
                @endif
            @empty
                <div class="col-12">
                    <div class="alert alert-light border text-muted mb-0">Belum ada template surat yang tersedia.</div>
                </div>
            @endforelse
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-1">Pengajuan Template Baru</h5>
            <p class="text-muted small mb-0">Usulan template dari kabag atau kasubag akan masuk ke super admin untuk ditindaklanjuti.</p>
        </div>
        @if(!$proposalModuleReady)
            <span class="badge badge-warning">Menunggu Aktivasi Schema</span>
        @endif
    </div>
    <div class="card-body p-0">
        @if($proposalModuleReady)
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Judul</th>
                            <th>Pengusul</th>
                            <th>Field Diminta</th>
                            <th>Contoh</th>
                            <th>Status</th>
                            @if($canManageTemplates)
                                <th class="text-right">Tindak Lanjut</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($proposals as $proposal)
                            @php
                                $requestedFields = collect($proposal->requested_fields ?: []);
                                $proposalFieldSchema = json_encode($requestedFields->map(function ($field) {
                                    return ['name' => Str::slug($field, '_'), 'label' => $field, 'type' => 'text', 'required' => true];
                                })->values(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                            @endphp
                            <tr>
                                <td>
                                    <div class="font-weight-600">{{ $proposal->title }}</div>
                                    <small class="text-muted">{{ $proposal->category }}{{ $proposal->description ? ' Ã¢â‚¬Â¢ ' . $proposal->description : '' }}</small>
                                </td>
                                <td>
                                    <div>{{ optional($proposal->requester)->name ?: '-' }}</div>
                                    <small class="text-muted">{{ optional($proposal->created_at)->translatedFormat('d M Y H:i') }}</small>
                                </td>
                                <td class="small text-muted">{{ $requestedFields->implode(', ') ?: '-' }}</td>
                                <td>
                                    <a href="{{ route('surat-template.sample', ['type' => 'proposal', 'id' => $proposal->id]) }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-paperclip"></i> Buka Contoh
                                    </a>
                                </td>
                                <td><span class="badge badge-{{ $proposal->status_badge_class }}">{{ $proposal->status_label }}</span></td>
                                @if($canManageTemplates)
                                    <td class="text-right">
                                        <div class="app-action-group justify-content-end">
                                            @if(in_array($proposal->status, ['submitted', 'in_review'], true))
                                                <button type="button" class="app-icon-btn edit" data-toggle="modal" data-target="#processProposalModal{{ $proposal->id }}"><i class="fas fa-check-circle"></i></button>
                                            @endif
                                        </div>
                                    </td>
                                @endif
                            </tr>

                            @if($canManageTemplates && in_array($proposal->status, ['submitted', 'in_review'], true))
                                <div class="modal fade" id="processProposalModal{{ $proposal->id }}" tabindex="-1">
                                    <div class="modal-dialog modal-xl"><div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Tindak Lanjut Pengajuan Template</h5>
                                            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                        </div>
                                        <form method="POST" action="{{ route('surat-template.proposals.process', $proposal) }}">
                                            @csrf
                                            <div class="modal-body">
                                                <div class="row">
                                                    <div class="col-md-6 form-group">
                                                        <label>Aksi</label>
                                                        <select name="action" class="form-control" required>
                                                            <option value="approve">Setujui dan Jadikan Template</option>
                                                            <option value="reject">Tolak Pengajuan</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6 form-group">
                                                        <label>Kategori Template</label>
                                                        <input type="text" name="template_category" class="form-control" value="{{ $proposal->category }}">
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6 form-group">
                                                        <label>Nama Template</label>
                                                        <input type="text" name="template_name" class="form-control" value="{{ $proposal->title }}">
                                                    </div>
                                                    <div class="col-md-6 form-group">
                                                        <label>Slug Template</label>
                                                        <input type="text" name="template_slug" class="form-control" value="{{ $proposal->slug ?: Str::slug($proposal->title) }}">
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6 form-group">
                                                        <label>Status Template</label>
                                                        <select name="template_status" class="form-control">
                                                            <option value="active">Aktif</option>
                                                            <option value="draft">Draft</option>
                                                            <option value="inactive">Nonaktif</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6 form-group">
                                                        <label>Catatan Review</label>
                                                        <input type="text" name="review_notes" class="form-control" value="{{ $proposal->review_notes }}">
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label>Field Schema (JSON)</label>
                                                    <textarea name="field_schema" class="form-control font-monospace" rows="8">{{ $proposalFieldSchema }}</textarea>
                                                </div>
                                                <div class="form-group mb-0">
                                                    <label>Body Template</label>
                                                    @php
                                                        $defaultProposalTemplateBody = $proposal->suggested_template_body;
                                                        if (!$defaultProposalTemplateBody) {
                                                            $defaultProposalTemplateBody = '<p><strong>' . e($proposal->title) . '</strong></p><p>Nomor: [nomor_surat]</p><p>Isi surat disusun berdasarkan template resmi yang disetujui.</p>';
                                                            $defaultProposalTemplateBody = str_replace('[nomor_surat]', '{{nomor_surat}}', $defaultProposalTemplateBody);
                                                        }
                                                    @endphp
                                                    <textarea name="template_body" class="form-control" rows="10">{{ $defaultProposalTemplateBody }}</textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-primary">Simpan Tindak Lanjut</button>
                                            </div>
                                        </form>
                                    </div></div>
                                </div>
                            @endif
                        @empty
                            <tr>
                                <td colspan="{{ $canManageTemplates ? 6 : 5 }}" class="text-center text-muted py-4">Belum ada pengajuan template baru.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-4 text-muted">Pengajuan template baru akan aktif setelah tabel `surat_template_proposals` dijalankan secara manual.</div>
        @endif
    </div>
</div>

@if($canManageTemplates && $moduleReady)
    <div class="modal fade" id="createTemplateModal" tabindex="-1">
        <div class="modal-dialog modal-xl"><div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Template Surat</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form method="POST" action="{{ route('surat-template.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">@include('surat-template.partials.template-form', ['item' => null, 'fieldSchemaJson' => "[\n  {\"name\": \"nomor_surat\", \"label\": \"Nomor Surat\", \"type\": \"text\", \"required\": true}\n]"])</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Template</button>
                </div>
            </form>
        </div></div>
    </div>
@endif

@if($canSubmitProposal && $proposalModuleReady)
    <div class="modal fade" id="createProposalModal" tabindex="-1">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajukan Template Baru</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form method="POST" action="{{ route('surat-template.proposals.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Nama Template</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Slug Usulan</label>
                            <input type="text" name="slug" class="form-control" placeholder="opsional, contoh: surat-tugas-kegiatan">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Kategori Template</label>
                        <input type="text" name="category" class="form-control" placeholder="Contoh: Penugasan, Keterangan, Undangan Internal" required>
                    </div>
                    <div class="form-group">
                        <label>Deskripsi Singkat</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Jelaskan fungsi template dan kapan dipakai."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Field yang Dibutuhkan</label>
                        <textarea name="requested_fields" class="form-control" rows="4" placeholder="Tulis satu field per baris. Contoh:\nNomor Surat\nNama Petugas\nTujuan Tugas" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Draft Body Template</label>
                        <textarea name="suggested_template_body" class="form-control" rows="6" placeholder="Opsional. Isi draft template jika sudah punya susunan isi surat."></textarea>
                    </div>
                    <div class="form-group mb-0">
                        <label>Upload Contoh Template</label>
                        <input type="file" name="example_file" class="form-control-file" required>
                        <small class="text-muted">Format yang diterima: PDF, DOC, DOCX, JPG, JPEG, PNG. Maksimal 10 MB.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Kirim Pengajuan</button>
                </div>
            </form>
        </div></div>
    </div>
@endif
@endsection

@push('scripts')
<script>
    $(function () {
        function createFieldRow(data) {
            const row = data || { label: '', name: '', type: 'text', required: false };

            return `
                <div class="row align-items-end mb-2 template-field-row">
                    <div class="col-md-3 form-group mb-2">
                        <label class="small text-muted">Label Field</label>
                        <input type="text" class="form-control form-control-sm" name="field_builder[label][]" value="${escapeHtml(row.label || '')}" placeholder="Contoh: Nama Pegawai">
                    </div>
                    <div class="col-md-3 form-group mb-2">
                        <label class="small text-muted">Nama Sistem</label>
                        <input type="text" class="form-control form-control-sm" name="field_builder[name][]" value="${escapeHtml(row.name || '')}" placeholder="contoh: nama_pegawai">
                    </div>
                    <div class="col-md-3 form-group mb-2">
                        <label class="small text-muted">Tipe</label>
                        <select class="form-control form-control-sm" name="field_builder[type][]">
                            <option value="text" ${row.type === 'text' ? 'selected' : ''}>Text</option>
                            <option value="textarea" ${row.type === 'textarea' ? 'selected' : ''}>Textarea</option>
                            <option value="date" ${row.type === 'date' ? 'selected' : ''}>Tanggal</option>
                            <option value="user_select" ${row.type === 'user_select' ? 'selected' : ''}>Pilih 1 User</option>
                            <option value="user_multi" ${row.type === 'user_multi' ? 'selected' : ''}>Pilih Banyak User</option>
                        </select>
                    </div>
                    <div class="col-md-2 form-group mb-2">
                        <label class="small text-muted d-block">Wajib Isi</label>
                        <label class="mb-0 mt-1">
                            <input type="checkbox" name="field_builder[required][]" value="1" ${row.required ? 'checked' : ''}> Ya
                        </label>
                    </div>
                    <div class="col-md-1 form-group mb-2 text-right">
                        <button type="button" class="btn btn-sm btn-outline-danger" data-remove-template-field><i class="fas fa-times"></i></button>
                    </div>
                </div>
            `;
        }

        function escapeHtml(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function slugify(value) {
            return String(value || '')
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '_')
                .replace(/^_+|_+$/g, '');
        }

        function syncTemplateSchema($editor) {
            const schema = [];

            $editor.find('.template-field-row').each(function () {
                const $row = $(this);
                const label = ($row.find('input[name="field_builder[label][]"]').val() || '').trim();
                let name = ($row.find('input[name="field_builder[name][]"]').val() || '').trim();
                const type = $row.find('select[name="field_builder[type][]"]').val() || 'text';
                const required = $row.find('input[name="field_builder[required][]"]').is(':checked');

                if (!label && !name) {
                    return;
                }

                if (!name && label) {
                    name = slugify(label);
                    $row.find('input[name="field_builder[name][]"]').val(name);
                }

                if (!name) {
                    return;
                }

                schema.push({
                    name: name,
                    label: label || name,
                    type: type,
                    required: required
                });
            });

            const json = JSON.stringify(schema, null, 2);
            $editor.find('.template-field-schema-output').val(json);
            $editor.find('.template-field-schema-preview').val(json);
        }

        $(document).on('click', '[data-add-template-field]', function () {
            const $editor = $(this).closest('.template-schema-editor');
            $editor.find('[data-template-field-list]').append(createFieldRow());
            syncTemplateSchema($editor);
        });

        $(document).on('click', '[data-remove-template-field]', function () {
            const $editor = $(this).closest('.template-schema-editor');
            $(this).closest('.template-field-row').remove();
            if ($editor.find('.template-field-row').length === 0) {
                $editor.find('[data-template-field-list]').append(createFieldRow({ name: 'nomor_surat', label: 'Nomor Surat', type: 'text', required: true }));
            }
            syncTemplateSchema($editor);
        });

        $(document).on('input change', '.template-schema-editor input, .template-schema-editor select', function () {
            syncTemplateSchema($(this).closest('.template-schema-editor'));
        });

        $('.template-schema-editor').each(function () {
            syncTemplateSchema($(this));
        });

        $('.modal').on('shown.bs.modal', function () {
            $(this).find('select.select2').each(function () {
                const $select = $(this);
                if ($select.hasClass('select2-hidden-accessible')) {
                    return;
                }

                $select.select2({
                    theme: 'bootstrap4',
                    width: '100%',
                    dropdownParent: $($select.data('dropdown-parent') || $select.closest('.modal'))
                });
            });
        });
    });
</script>
@endpush

