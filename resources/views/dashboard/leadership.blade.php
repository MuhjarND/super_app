@extends('layouts.app')

@section('title', 'Dashboard Pimpinan')

@push('styles')
<style>
.leadership-grid{display:grid;gap:18px;}
.leadership-stats{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:14px;}
.leadership-card{background:#fff;border:1px solid #e5e7eb;border-radius:18px;box-shadow:0 10px 24px rgba(15,23,42,.05);overflow:hidden;}
.leadership-card-head{padding:18px 20px 12px;border-bottom:1px solid #eef2f7;display:flex;justify-content:space-between;gap:12px;align-items:flex-start;}
.leadership-card-head h3{margin:0;font-size:1rem;font-weight:800;color:#0f172a;}
.leadership-card-head p{margin:4px 0 0;font-size:.8rem;color:#64748b;}
.leadership-card-body{padding:16px 20px;}
.leadership-stat{background:#fff;border:1px solid #dbe6f3;border-radius:16px;padding:16px;min-height:96px;}
.leadership-stat .value{font-size:1.6rem;font-weight:800;color:#0f172a;line-height:1;margin-bottom:8px;}
.leadership-stat .label{font-size:.8rem;color:#64748b;line-height:1.35;}
.leadership-row{display:grid;grid-template-columns:1.15fr .85fr;gap:18px;}
.leadership-list{display:grid;gap:10px;}
.leadership-item{padding:12px 0;border-bottom:1px solid #eef2f7;display:grid;grid-template-columns:1fr auto;gap:12px;align-items:flex-start;}
.leadership-item:last-child{border-bottom:none;}
.leadership-item-title{font-size:.86rem;font-weight:700;color:#0f172a;margin-bottom:4px;}
.leadership-item-meta,.leadership-item-desc{font-size:.76rem;color:#64748b;line-height:1.4;}
.leadership-chip{display:inline-flex;align-items:center;gap:8px;border-radius:999px;padding:8px 12px;font-size:.74rem;font-weight:700;background:#eff6ff;color:#1d4ed8;}
.leadership-chip.red{background:#fee2e2;color:#b91c1c;}
.leadership-chip.amber{background:#fff7ed;color:#c2410c;}
.leadership-chip.green{background:#dcfce7;color:#166534;}
@media (max-width: 1199.98px){.leadership-stats{grid-template-columns:repeat(3,minmax(0,1fr));}.leadership-row{grid-template-columns:1fr;}}
@media (max-width: 767.98px){.leadership-stats{grid-template-columns:repeat(2,minmax(0,1fr));}.leadership-card,.leadership-stat{border-radius:16px;}}
</style>
@endpush

@section('content-header')
<div class="content-header">
    <div class="container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-start" style="gap:12px;">
            <div>
                <h1 class="m-0">Dashboard Pimpinan</h1>
                <small class="text-muted">Ringkasan pengawasan persetujuan, disposisi, agenda, dan progres lintas modul.</small>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="container-fluid leadership-grid">
    <section class="leadership-stats">
        <div class="leadership-stat"><div class="value">{{ $summary['pending_approvals'] }}</div><div class="label">Persetujuan lintas modul yang masih pending</div></div>
        <div class="leadership-stat"><div class="value">{{ $summary['pending_dispositions'] }}</div><div class="label">Disposisi surat yang belum selesai</div></div>
        <div class="leadership-stat"><div class="value">{{ $summary['today_agenda'] }}</div><div class="label">Agenda dan rapat hari ini</div></div>
        <div class="leadership-stat"><div class="value">{{ $summary['active_leave'] }}</div><div class="label">Cuti aktif pada hari ini</div></div>
        <div class="leadership-stat"><div class="value">{{ $summary['urgent_actions'] }}</div><div class="label">Item prioritas tinggi</div></div>
        <div class="leadership-stat"><div class="value">{{ $summary['overdue_actions'] }}</div><div class="label">Item yang sudah overdue</div></div>
    </section>

    <section class="leadership-row">
        <div class="leadership-card">
            <div class="leadership-card-head">
                <div>
                    <h3>Atensi Pimpinan</h3>
                    <p>Daftar item lintas modul yang paling perlu segera diproses.</p>
                </div>
                <a href="{{ route('action-center.index') }}" class="btn btn-sm btn-primary">Buka Tindak Lanjut</a>
            </div>
            <div class="leadership-card-body">
                @if($actionItems->isEmpty())
                    <div class="text-muted">Tidak ada item aktif.</div>
                @else
                    <div class="leadership-list">
                        @foreach($actionItems as $item)
                            <div class="leadership-item">
                                <div>
                                    <div class="leadership-item-title">{{ $item['type_label'] ?? 'Tindak Lanjut' }} - {{ $item['title'] ?? '-' }}</div>
                                    <div class="leadership-item-desc">{{ $item['description'] ?? '-' }}</div>
                                    <div class="leadership-item-meta">{{ $item['module_label'] ?? '-' }} | {{ !empty($item['target_at']) ? $item['target_at']->translatedFormat('d M Y H:i') : '-' }}</div>
                                </div>
                                <a href="{{ $item['action_url'] ?? route('action-center.index') }}" class="btn btn-sm btn-light border">Buka</a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="leadership-card">
            <div class="leadership-card-head">
                <div>
                    <h3>Persetujuan Pending</h3>
                    <p>Ringkasan pending approval dan review per modul.</p>
                </div>
            </div>
            <div class="leadership-card-body d-grid" style="gap:10px;">
                <div class="leadership-chip">Undangan Rapat <strong>{{ $approvalSummary['rapat'] }}</strong></div>
                <div class="leadership-chip">Notulensi <strong>{{ $approvalSummary['notulensi'] }}</strong></div>
                <div class="leadership-chip green">Cuti <strong>{{ $approvalSummary['cuti'] }}</strong></div>
                <div class="leadership-chip amber">Surat Keluar <strong>{{ $approvalSummary['surat'] }}</strong></div>
                <div class="leadership-chip red">Progress ZI <strong>{{ $approvalSummary['zi'] }}</strong></div>
            </div>
        </div>
    </section>

    <section class="leadership-row">
        <div class="leadership-card">
            <div class="leadership-card-head">
                <div>
                    <h3>Disposisi Pending</h3>
                    <p>Disposisi surat yang perlu pengawasan lebih dekat.</p>
                </div>
                <a href="{{ route('surat-masuk.index') }}" class="btn btn-sm btn-light border">Buka Persuratan</a>
            </div>
            <div class="leadership-card-body">
                @if($pendingDisposisi->isEmpty())
                    <div class="text-muted">Tidak ada disposisi pending.</div>
                @else
                    <div class="leadership-list">
                        @foreach($pendingDisposisi as $disposisi)
                            <div class="leadership-item">
                                <div>
                                    <div class="leadership-item-title">{{ optional($disposisi->suratMasuk)->nomor_surat ?: '-' }} - {{ optional($disposisi->suratMasuk)->perihal ?: '-' }}</div>
                                    <div class="leadership-item-desc">Dari {{ optional($disposisi->dariUser)->name ?: '-' }} kepada {{ optional($disposisi->kepadaUser)->name ?: '-' }}</div>
                                    <div class="leadership-item-meta">Prioritas {{ ucfirst($disposisi->priority_level ?: 'normal') }} | Target {{ $disposisi->target_label }}</div>
                                </div>
                                <span class="leadership-chip {{ $disposisi->is_overdue ? 'red' : ($disposisi->priority_level === 'high' ? 'amber' : '') }}">{{ $disposisi->status }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="leadership-card">
            <div class="leadership-card-head">
                <div>
                    <h3>Agenda 7 Hari Ke Depan</h3>
                    <p>Rapat, agenda pimpinan, cuti, dan agenda utama lain yang akan datang.</p>
                </div>
                <a href="{{ route('calendar.integrated.index') }}" class="btn btn-sm btn-light border">Buka Kalender</a>
            </div>
            <div class="leadership-card-body">
                @if($upcomingEvents->isEmpty())
                    <div class="text-muted">Belum ada agenda 7 hari ke depan.</div>
                @else
                    <div class="leadership-list">
                        @foreach($upcomingEvents as $event)
                            <div class="leadership-item">
                                <div>
                                    <div class="leadership-item-title">{{ $event['title'] }}</div>
                                    <div class="leadership-item-desc">{{ data_get($event, 'extendedProps.module_label', '-') }} | {{ data_get($event, 'extendedProps.status_label', '-') }}</div>
                                    <div class="leadership-item-meta">{{ \Carbon\Carbon::parse($event['start'], 'Asia/Jayapura')->translatedFormat('d M Y H:i') }}</div>
                                </div>
                                @if(!empty($event['url']))
                                    <a href="{{ $event['url'] }}" class="btn btn-sm btn-light border">Buka</a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>

    <section class="leadership-row">
        <div class="leadership-card">
            <div class="leadership-card-head"><div><h3>Progress ZI</h3><p>Status atensi pada periode aktif.</p></div></div>
            <div class="leadership-card-body d-grid" style="gap:10px;">
                <div class="leadership-chip">Periode Aktif <strong>{{ $ziStats['period_name'] }}</strong></div>
                <div class="leadership-chip amber">Kegiatan Overdue <strong>{{ $ziStats['overdue_count'] }}</strong></div>
                <div class="leadership-chip red">Review Pimpinan Pending <strong>{{ $ziStats['approval_pending'] }}</strong></div>
            </div>
        </div>
        <div class="leadership-card">
            <div class="leadership-card-head"><div><h3>Perawatan Alat dan Mesin</h3><p>Ringkasan item yang masih perlu diperhatikan.</p></div></div>
            <div class="leadership-card-body d-grid" style="gap:10px;">
                <div class="leadership-chip">Draft Perawatan <strong>{{ $inventoryStats['draft_count'] }}</strong></div>
                <div class="leadership-chip amber">Lampiran Belum Lengkap <strong>{{ $inventoryStats['attachment_pending_count'] }}</strong></div>
            </div>
        </div>
    </section>
</div>
@endsection
