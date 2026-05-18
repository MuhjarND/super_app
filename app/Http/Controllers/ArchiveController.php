<?php

namespace App\Http\Controllers;

use App\AgendaPimpinan;
use App\LeaveRequest;
use App\Rapat;
use App\SuratKeluar;
use App\SuratMasuk;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ArchiveController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        abort_unless($user && $user->canAccessArchiveMenu(), 403);

        $items = collect()
            ->merge($this->buildSuratMasukItems($user))
            ->merge($this->buildSuratKeluarItems($user))
            ->merge($this->buildRapatItems($user))
            ->merge($this->buildAgendaItems($user))
            ->merge($this->buildLeaveItems($user));

        if ($request->filled('search')) {
            $search = mb_strtolower(trim((string) $request->search));
            $items = $items->filter(function ($item) use ($search) {
                return str_contains(mb_strtolower($item['number_plain']), $search)
                    || str_contains(mb_strtolower($item['category_plain']), $search)
                    || str_contains(mb_strtolower($item['subject_plain']), $search)
                    || str_contains(mb_strtolower($item['recipient_plain']), $search)
                    || str_contains(mb_strtolower($item['type_plain']), $search)
                    || str_contains(mb_strtolower($item['creator_plain']), $search);
            });
        }

        if ($request->filled('type')) {
            $type = trim((string) $request->type);
            $items = $items->where('type', $type);
        }

        $items = $items->sortByDesc('sort_timestamp')->values();

        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 15;
        $pagedItems = $items->slice(($page - 1) * $perPage, $perPage)->values();
        $archives = new LengthAwarePaginator(
            $pagedItems,
            $items->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('arsip.index', [
            'archives' => $archives,
            'filters' => $request->only('search', 'type'),
        ]);
    }

    protected function buildSuratMasukItems($user)
    {
        return SuratMasuk::visibleTo($user)
            ->with(['kategoriSurat', 'klasifikasiKode', 'creator', 'disposisis.kepadaUser', 'disposisis.kepadaJabatan'])
            ->where('status', 'selesai')
            ->get()
            ->map(function ($surat) {
                $latestDisposisi = $surat->disposisis->sortByDesc('created_at')->first();
                $recipientName = optional($latestDisposisi->kepadaUser)->name ?: optional($latestDisposisi->kepadaJabatan)->nama ?: '-';

                return $this->buildArchiveItem([
                    'type' => 'surat_masuk',
                    'type_label' => 'Surat Masuk',
                    'number' => e($surat->nomor_surat),
                    'number_plain' => $surat->nomor_surat,
                    'category' => e(optional($surat->kategoriSurat)->kode ? optional($surat->kategoriSurat)->kode . ' - ' . optional($surat->kategoriSurat)->nama : '-'),
                    'category_plain' => optional($surat->kategoriSurat)->kode . ' ' . optional($surat->kategoriSurat)->nama,
                    'subject' => e($surat->perihal),
                    'subject_plain' => $surat->perihal,
                    'recipient' => e($recipientName),
                    'recipient_plain' => $recipientName,
                    'date' => optional($surat->tanggal_surat)->translatedFormat('Y-m-d'),
                    'input_date' => optional($surat->created_at)->translatedFormat('Y-m-d'),
                    'file_url' => route('surat-masuk.preview', $surat),
                    'file_label' => 'Berkas',
                    'creator' => e(optional($surat->creator)->name ?: '-'),
                    'creator_plain' => optional($surat->creator)->name ?: '-',
                    'status_html' => $surat->status_badge,
                    'sort_timestamp' => optional($surat->created_at)->timestamp ?: 0,
                ]);
            });
    }

    protected function buildSuratKeluarItems($user)
    {
        return SuratKeluar::visibleTo($user)
            ->with(['kategoriSurat', 'creator', 'penerimaInternal', 'templateApproval', 'pdfVerifications', 'rapat', 'leaveRequest'])
            ->where('status', 'lengkap')
            ->get()
            ->map(function ($surat) {
                $recipientText = $surat->opsi_penerima === 'internal'
                    ? 'Internal / ' . $surat->penerimaInternal->count() . ' orang'
                    : 'External / ' . ($surat->penerima_external ?: '-');

                return $this->buildArchiveItem([
                    'type' => 'surat_keluar',
                    'type_label' => 'Surat Keluar',
                    'number' => e($surat->nomor_surat_formatted),
                    'number_plain' => $surat->nomor_surat_formatted,
                    'category' => e(optional($surat->kategoriSurat)->kode ? optional($surat->kategoriSurat)->kode . ' - ' . optional($surat->kategoriSurat)->nama : '-'),
                    'category_plain' => optional($surat->kategoriSurat)->kode . ' ' . optional($surat->kategoriSurat)->nama,
                    'subject' => e($surat->perihal),
                    'subject_plain' => $surat->perihal,
                    'recipient' => e($recipientText),
                    'recipient_plain' => $recipientText,
                    'date' => optional($surat->tanggal_surat)->translatedFormat('Y-m-d'),
                    'input_date' => optional($surat->created_at)->translatedFormat('Y-m-d'),
                    'file_url' => ($surat->file_path || $surat->templateApproval || $surat->rapat || $surat->leaveRequest || $surat->pdfVerifications->isNotEmpty()) ? route('surat-keluar.file', $surat) : null,
                    'file_label' => ($surat->file_path || $surat->templateApproval || $surat->rapat || $surat->leaveRequest || $surat->pdfVerifications->isNotEmpty()) ? 'Berkas' : '-',
                    'creator' => e(optional($surat->creator)->name ?: '-'),
                    'creator_plain' => optional($surat->creator)->name ?: '-',
                    'status_html' => $surat->status_badge,
                    'sort_timestamp' => optional($surat->created_at)->timestamp ?: 0,
                ]);
            });
    }

    protected function buildRapatItems($user)
    {
        return Rapat::visibleTo($user)
            ->with(['creator', 'kategoriSuratKode', 'pesertas', 'laporans'])
            ->whereIn('status', ['disetujui', 'selesai'])
            ->get()
            ->map(function ($rapat) {
                $recipientText = 'Internal / ' . $rapat->pesertas->count() . ' orang';
                $tindakLanjutLaporan = $rapat->laporans
                    ->where('jenis', 'tindak_lanjut')
                    ->sortByDesc(function ($laporan) {
                        return optional($laporan->updated_at)->timestamp ?: 0;
                    })
                    ->first();

                return $this->buildArchiveItem([
                    'type' => 'rapat',
                    'type_label' => 'Rapat / Agenda',
                    'number' => e($rapat->nomor_undangan ?: '-'),
                    'number_plain' => $rapat->nomor_undangan ?: '-',
                    'category' => e($rapat->kategori_surat_label ?: ($rapat->kategoriSuratKode ? $rapat->kategoriSuratKode->kode . ' - ' . $rapat->kategoriSuratKode->nama : '-')),
                    'category_plain' => $rapat->kategori_surat_label ?: optional($rapat->kategoriSuratKode)->nama,
                    'subject' => e($rapat->judul),
                    'subject_plain' => $rapat->judul,
                    'recipient' => e($recipientText),
                    'recipient_plain' => $recipientText,
                    'date' => optional($rapat->tanggal)->translatedFormat('Y-m-d'),
                    'input_date' => optional($rapat->created_at)->translatedFormat('Y-m-d'),
                    'file_url' => $tindakLanjutLaporan
                        ? route('rapat.laporan.preview', $tindakLanjutLaporan)
                        : route('rapat.undangan.preview', $rapat),
                    'file_label' => 'Berkas',
                    'creator' => e(optional($rapat->creator)->name ?: '-'),
                    'creator_plain' => optional($rapat->creator)->name ?: '-',
                    'status_html' => $rapat->status_badge,
                    'sort_timestamp' => optional($rapat->created_at)->timestamp ?: 0,
                ]);
            });
    }

    protected function buildAgendaItems($user)
    {
        $query = AgendaPimpinan::with(['creator', 'recipients']);

        if (!$user->isSuperAdmin() && !$user->canAccessAgendaPimpinan()) {
            $query->where(function ($builder) use ($user) {
                $builder->where('created_by', $user->id)
                    ->orWhereHas('recipients', function ($recipientQuery) use ($user) {
                        $recipientQuery->where('users.id', $user->id);
                    });
            });
        }

        return $query->get()->map(function ($agenda) {
            $recipientText = 'Internal / ' . $agenda->recipients->count() . ' orang';

            return $this->buildArchiveItem([
                'type' => 'agenda',
                'type_label' => 'Rapat / Agenda',
                'number' => e($agenda->nomor_naskah_dinas ?: '-'),
                'number_plain' => $agenda->nomor_naskah_dinas ?: '-',
                'category' => 'Agenda Pimpinan',
                'category_plain' => 'Agenda Pimpinan',
                'subject' => e($agenda->judul_agenda),
                'subject_plain' => $agenda->judul_agenda,
                'recipient' => e($recipientText),
                'recipient_plain' => $recipientText,
                'date' => optional($agenda->tanggal_kegiatan)->translatedFormat('Y-m-d'),
                'input_date' => optional($agenda->created_at)->translatedFormat('Y-m-d'),
                'file_url' => $agenda->lampiran_link ?: null,
                'file_label' => $agenda->lampiran_link ? 'Berkas' : '-',
                'creator' => e(optional($agenda->creator)->name ?: '-'),
                'creator_plain' => optional($agenda->creator)->name ?: '-',
                'status_html' => '<span class="badge badge-success app-status-badge">Aktif</span>',
                'sort_timestamp' => optional($agenda->created_at)->timestamp ?: 0,
                'file_external' => true,
            ]);
        });
    }

    protected function buildLeaveItems($user)
    {
        $query = LeaveRequest::with(['user', 'leaveType', 'creator', 'approvals']);

        if (!$user->isSuperAdmin() && !$user->canApproveLeave()) {
            $query->where('user_id', $user->id);
        }

        return $query
            ->whereIn('status', [LeaveRequest::STATUS_APPROVED, LeaveRequest::STATUS_REJECTED, LeaveRequest::STATUS_COMPLETED])
            ->get()
            ->map(function ($leaveRequest) {
                $recipientText = 'Internal / ' . max(1, $leaveRequest->approvals->count()) . ' orang';
                $subject = 'Permintaan dan Pemberian ' . (optional($leaveRequest->leaveType)->name ?: 'Cuti') . ' - ' . (optional($leaveRequest->user)->name ?: '-');

                return $this->buildArchiveItem([
                    'type' => 'cuti',
                    'type_label' => 'Cuti',
                    'number' => e($leaveRequest->display_number),
                    'number_plain' => $leaveRequest->display_number,
                    'category' => e(optional($leaveRequest->leaveType)->name ?: '-'),
                    'category_plain' => optional($leaveRequest->leaveType)->name ?: '-',
                    'subject' => e($subject),
                    'subject_plain' => $subject,
                    'recipient' => e($recipientText),
                    'recipient_plain' => $recipientText,
                    'date' => optional($leaveRequest->start_date)->translatedFormat('Y-m-d'),
                    'input_date' => optional($leaveRequest->created_at)->translatedFormat('Y-m-d'),
                    'file_url' => route('cuti.surat', $leaveRequest),
                    'file_label' => 'Berkas',
                    'creator' => e(optional($leaveRequest->creator)->name ?: optional($leaveRequest->user)->name ?: '-'),
                    'creator_plain' => optional($leaveRequest->creator)->name ?: optional($leaveRequest->user)->name ?: '-',
                    'status_html' => $leaveRequest->status_badge,
                    'sort_timestamp' => optional($leaveRequest->created_at)->timestamp ?: 0,
                ]);
            });
    }

    protected function buildArchiveItem(array $data)
    {
        return array_merge([
            'type' => '',
            'type_label' => '',
            'number' => '-',
            'number_plain' => '-',
            'category' => '-',
            'category_plain' => '-',
            'subject' => '-',
            'subject_plain' => '-',
            'recipient' => '-',
            'recipient_plain' => '-',
            'date' => '-',
            'input_date' => '-',
            'file_url' => null,
            'file_label' => '-',
            'creator' => '-',
            'creator_plain' => '-',
            'status_html' => '<span class="badge badge-secondary app-status-badge">-</span>',
            'sort_timestamp' => 0,
            'file_external' => false,
        ], $data);
    }
}
