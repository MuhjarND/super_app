@extends('layouts.app')

@section('title', 'Pengaturan Modul')

@push('styles')
<style>
    .module-settings-intro{display:flex;justify-content:space-between;align-items:center;gap:18px;padding:20px 22px;margin-bottom:18px;border:1px solid #ddd6fe;border-radius:18px;background:linear-gradient(135deg,#fff,#f5f3ff)}
    .module-settings-intro h2{margin:0 0 5px;font-size:1.15rem;font-weight:800}.module-settings-intro p{margin:0;color:#64748b;font-size:.86rem}
    .module-settings-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.module-setting-card{position:relative;padding:20px;border:1px solid #e2e8f0;border-radius:18px;background:#fff;box-shadow:0 10px 30px rgba(15,23,42,.045)}
    .module-setting-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:16px}.module-setting-identity{display:flex;gap:12px;min-width:0}.module-setting-icon{width:44px;height:44px;flex:0 0 44px;display:grid;place-items:center;border-radius:13px;background:#ede9fe;color:#6d28d9}.module-setting-name{font-weight:800;color:#172033}.module-setting-desc{margin-top:3px;color:#64748b;font-size:.78rem;line-height:1.45}
    .module-setting-status{min-width:145px}.module-setting-fields{display:grid;grid-template-columns:1fr;gap:12px}.module-setting-fields .full{grid-column:1/-1}.module-setting-switches{display:flex;flex-wrap:wrap;gap:12px;padding-top:4px}.module-setting-switch{display:inline-flex;align-items:center;gap:7px;font-size:.8rem;font-weight:700;color:#475569}.module-setting-switch input{width:17px;height:17px}.module-settings-save{position:sticky;bottom:16px;z-index:5;display:flex;justify-content:flex-end;margin-top:18px}.module-settings-save .btn{padding:11px 22px;border-radius:12px;box-shadow:0 12px 28px rgba(109,40,217,.22)}
    @media(max-width:991.98px){.module-settings-grid{grid-template-columns:1fr}}@media(max-width:575.98px){.module-settings-intro{align-items:flex-start}.module-setting-head{display:block}.module-setting-status{width:100%;margin-top:12px}.module-setting-fields{grid-template-columns:1fr}.module-setting-fields .full{grid-column:auto}.module-settings-save .btn{width:100%}}
</style>
@endpush

@section('content-header')
<div class="content-header"><div class="container-fluid"><h1>Pengaturan Modul</h1></div></div>
@endsection

@section('content')
    @include('admin._alerts')
    <div class="module-settings-intro">
        <div><h2>Kontrol publikasi aplikasi</h2><p>Atur ketersediaan, nama menu, visibilitas perangkat, dan informasi maintenance setiap modul.</p></div>
        <i class="fas fa-sliders-h text-primary fa-lg"></i>
    </div>

    <form method="POST" action="{{ route('admin.module-settings.update') }}">
        @csrf
        @method('PUT')
        <div class="module-settings-grid">
            @foreach($modules as $key => $module)
                <article class="module-setting-card">
                    <div class="module-setting-head">
                        <div class="module-setting-identity">
                            <span class="module-setting-icon"><i class="{{ $module['icon'] }}"></i></span>
                            <div><div class="module-setting-name">{{ $module['name'] }}</div><div class="module-setting-desc">{{ $module['description'] }}</div></div>
                        </div>
                        <select name="modules[{{ $key }}][status]" class="form-control module-setting-status">
                            <option value="published" {{ $module['status'] === 'published' ? 'selected' : '' }}>Dipublikasikan</option>
                            <option value="maintenance" {{ $module['status'] === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                            <option value="draft" {{ $module['status'] === 'draft' ? 'selected' : '' }}>Draft / Sembunyikan</option>
                        </select>
                    </div>
                    <div class="module-setting-fields">
                        <div class="form-group mb-0"><label>Nama Menu</label><input type="text" class="form-control" name="modules[{{ $key }}][custom_label]" value="{{ old('modules.'.$key.'.custom_label', optional($module['record'])->custom_label) }}" placeholder="{{ $module['name'] }}"></div>
                        <div class="form-group mb-0 full"><label>Pesan Maintenance</label><textarea class="form-control" rows="2" name="modules[{{ $key }}][maintenance_message]" placeholder="Pesan yang dilihat pengguna">{{ old('modules.'.$key.'.maintenance_message', optional($module['record'])->maintenance_message) }}</textarea></div>
                        <div class="module-setting-switches full">
                            <input type="hidden" name="modules[{{ $key }}][show_desktop]" value="0"><label class="module-setting-switch"><input type="checkbox" name="modules[{{ $key }}][show_desktop]" value="1" {{ $module['show_desktop'] ? 'checked' : '' }}> Desktop</label>
                            <input type="hidden" name="modules[{{ $key }}][show_mobile]" value="0"><label class="module-setting-switch"><input type="checkbox" name="modules[{{ $key }}][show_mobile]" value="1" {{ $module['show_mobile'] ? 'checked' : '' }}> Mobile</label>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="module-settings-save"><button type="submit" class="btn btn-primary"><i class="fas fa-save mr-2"></i>Simpan Pengaturan</button></div>
    </form>
@endsection
