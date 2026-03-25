@extends('layouts.app')

@section('title', 'Preview Template Surat')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-1">Preview Template Surat</h3>
        <p class="text-muted mb-0">Hasil pengisian field dari template surat yang dipilih.</p>
    </div>
    <div class="d-flex" style="gap:8px;">
        @if($canManageSuratKeluar)
            <form method="POST" action="{{ route('surat-template.handoff', data_get($template, 'slug')) }}" class="d-inline">
                @csrf
                @foreach($fieldValues as $fieldName => $fieldValue)
                    @if(is_array($fieldValue))
                        @foreach($fieldValue as $nestedValue)
                            @if(is_scalar($nestedValue) || is_null($nestedValue))
                                <input type="hidden" name="fields[{{ $fieldName }}][]" value="{{ $nestedValue }}">
                            @endif
                        @endforeach
                    @elseif(is_scalar($fieldValue) || is_null($fieldValue))
                        <input type="hidden" name="fields[{{ $fieldName }}]" value="{{ $fieldValue }}">
                    @endif
                @endforeach
                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> {{ $templateSlug === 'surat-tugas' ? 'Buat Surat Tugas' : 'Bawa ke Surat Keluar' }}</button>
            </form>
        @endif
        <a href="{{ route('surat-template.index') }}" class="btn btn-outline-secondary">Kembali</a>
    </div>
</div>

<div class="row">
    <div class="col-lg-4 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0">Informasi Template</h5>
            </div>
            <div class="card-body">
                <dl class="mb-0 small">
                    <dt class="text-muted">Nama Template</dt>
                    <dd class="mb-3">{{ data_get($template, 'name') }}</dd>
                    <dt class="text-muted">Kategori</dt>
                    <dd class="mb-3">{{ data_get($template, 'category') }}</dd>
                    <dt class="text-muted">Default Persuratan</dt>
                    <dd class="mb-3">
                        <div>Kategori: {{ $prefill['kategori_surat_label'] ?? '-' }}</div>
                        <div>Penandatangan: {{ $prefill['nomenklatur_label'] ?? ucfirst(str_replace('_', ' ', $prefill['nomenklatur_jabatan'] ?? 'sekretaris')) }}</div>
                        <div>Penerima: {{ ucfirst($prefill['opsi_penerima'] ?? 'internal') }}</div>
                    </dd>
                    <dt class="text-muted">Field Terisi</dt>
                    <dd class="mb-0">
                        @foreach($fieldSchema as $field)
                            <div class="mb-2">
                                <div class="text-muted">{{ $field['label'] ?? $field['name'] }}</div>
                                <div>{{ $fieldValues[$field['name']] ?? '-' }}</div>
                            </div>
                        @endforeach
                    </dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-lg-8 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Preview Isi Surat</h5>
                <span class="badge badge-primary">Template Dinamis</span>
            </div>
            <div class="card-body template-preview-body">
                @if($templateSlug === 'surat-tugas')
                    @include('surat-template.renderers.surat-tugas-preview', ['fieldValues' => $fieldValues])
                @else
                    {!! $renderedBody !!}
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

