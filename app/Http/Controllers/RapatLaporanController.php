<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadRapatLaporanRequest;
use App\RapatLaporan;
use App\Services\RapatLaporanService;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Support\Facades\Storage;

class RapatLaporanController extends Controller
{
    protected $laporanService;

    public function __construct(RapatLaporanService $laporanService)
    {
        $this->middleware('auth');
        $this->laporanService = $laporanService;
    }

    public function index()
    {
        abort_unless(auth()->user()->canAccessMeetingModule(), 403);

        $rapats = \App\Rapat::visibleTo(auth()->user())
            ->with([
                'kategoriSuratKode',
                'creator',
                'notulensi.notulis',
                'attendances',
                'laporans',
            ])
            ->orderByDesc('tanggal')
            ->orderByDesc('waktu_mulai')
            ->get();

        $this->laporanService->ensureForRapats($rapats, auth()->id());

        $laporans = RapatLaporan::with(['rapat.kategoriSuratKode', 'rapat.creator', 'rapat.notulensi'])
            ->whereIn('rapat_id', $rapats->pluck('id'))
            ->whereNull('archived_at')
            ->orderByDesc('updated_at')
            ->get();

        return view('rapat.laporan.index', compact('laporans'));
    }

    public function arsip()
    {
        abort_unless(auth()->user()->canAccessMeetingModule(), 403);

        $rapatIds = \App\Rapat::visibleTo(auth()->user())->pluck('id');

        $laporans = RapatLaporan::with(['rapat.kategoriSuratKode', 'rapat.creator'])
            ->whereIn('rapat_id', $rapatIds)
            ->whereNotNull('archived_at')
            ->orderByDesc('archived_at')
            ->get();

        return view('rapat.laporan.arsip', compact('laporans'));
    }

    public function preview(RapatLaporan $laporan)
    {
        abort_unless(auth()->user()->canViewRapat($laporan->rapat), 403);

        if ($laporan->file_path) {
            return response()->file(storage_path('app/public/' . $laporan->file_path));
        }

        return $this->buildPdfResponse($laporan, false);
    }

    public function download(RapatLaporan $laporan)
    {
        abort_unless(auth()->user()->canViewRapat($laporan->rapat), 403);

        if ($laporan->file_path) {
            return response()->download(storage_path('app/public/' . $laporan->file_path), $laporan->file_nama ?: basename($laporan->file_path));
        }

        return $this->buildPdfResponse($laporan, true);
    }

    public function upload(UploadRapatLaporanRequest $request, RapatLaporan $laporan)
    {
        abort_unless(auth()->user()->canAccessMeetingMinutes(), 403);

        $file = $request->file('laporan_file');

        if ($laporan->file_path) {
            Storage::disk('public')->delete($laporan->file_path);
        }

        $path = $file->store('rapat/laporan', 'public');

        $laporan->update([
            'file_path' => $path,
            'file_nama' => $file->getClientOriginalName(),
            'file_mime' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'deskripsi' => $request->input('deskripsi_upload') ?: $laporan->deskripsi,
            'updated_by' => auth()->id(),
            'is_ready' => true,
            'status' => $laporan->archived_at ? 'arsip' : 'aktif',
        ]);

        return back()->with('success', 'File laporan berhasil diupload sebagai file final.');
    }

    public function archive(RapatLaporan $laporan)
    {
        abort_unless(auth()->user()->canAccessMeetingMinutes(), 403);

        $laporan->update([
            'archived_at' => now(),
            'status' => 'arsip',
            'updated_by' => auth()->id(),
        ]);

        return back()->with('success', 'Laporan berhasil diarsipkan.');
    }

    public function unarchive(RapatLaporan $laporan)
    {
        abort_unless(auth()->user()->canAccessMeetingMinutes(), 403);

        $laporan->update([
            'archived_at' => null,
            'status' => 'aktif',
            'updated_by' => auth()->id(),
        ]);

        return back()->with('success', 'Laporan berhasil dikembalikan ke daftar aktif.');
    }

    protected function buildPdfResponse(RapatLaporan $laporan, $download = false)
    {
        $laporan->load([
            'rapat.kategoriSuratKode',
            'rapat.creator',
            'rapat.pesertas',
            'rapat.attendances',
            'rapat.notulensi.notulis',
        ]);

        if ($laporan->jenis === 'tindak_lanjut') {
            $view = 'rapat.laporan.pdf.tindak-lanjut';
        } else {
            $view = 'rapat.laporan.pdf.gabungan';
        }

        $pdf = PDF::loadView($view, [
            'laporan' => $laporan,
            'rapat' => $laporan->rapat,
            'notulensi' => $laporan->rapat->notulensi,
            'attendances' => $laporan->rapat->attendances,
        ])->setPaper('a4', 'portrait');

        $filename = str_replace(' ', '-', strtolower($laporan->judul)) . '.pdf';

        return $download ? $pdf->download($filename) : $pdf->stream($filename);
    }
}
