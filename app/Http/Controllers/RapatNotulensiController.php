<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRapatNotulensiRequest;
use App\Http\Requests\UploadRapatNotulensiRequest;
use App\Rapat;
use App\RapatNotulensi;
use App\User;
use Barryvdh\DomPDF\Facade as PDF;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RapatNotulensiController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        abort_unless(auth()->user()->canAccessMeetingMinutes(), 403);

        $rapats = Rapat::with(['kategoriSuratKode', 'creator', 'notulensi.notulis'])
            ->orderByDesc('tanggal')
            ->orderByDesc('waktu_mulai')
            ->get();

        $belumAda = $rapats->filter(function ($rapat) {
            return !$rapat->notulensi;
        })->values();

        $sudahAda = $rapats->filter(function ($rapat) {
            return (bool) $rapat->notulensi;
        })->values();

        return view('rapat.notulensi.index', compact('belumAda', 'sudahAda'));
    }

    public function create(Rapat $rapat)
    {
        abort_unless(auth()->user()->canAccessMeetingMinutes(), 403);

        if ($rapat->notulensi) {
            return redirect()->route('rapat.notulensi.edit', $rapat->notulensi);
        }

        $notulisOptions = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['notulis', 'operator', 'admin']);
        })->orderBy('hirarki')->orderBy('name')->get();

        return view('rapat.notulensi.form', [
            'rapat' => $rapat,
            'notulensi' => new RapatNotulensi(['mode' => 'template_a']),
            'notulisOptions' => $notulisOptions,
            'formAction' => route('rapat.notulensi.store', $rapat),
            'formMethod' => 'POST',
            'pageTitle' => 'Buat Notulensi',
        ]);
    }

    public function store(StoreRapatNotulensiRequest $request, Rapat $rapat)
    {
        abort_unless(auth()->user()->canAccessMeetingMinutes(), 403);

        if ($rapat->notulensi) {
            return redirect()->route('rapat.notulensi.edit', $rapat->notulensi)
                ->with('success', 'Notulensi untuk rapat ini sudah ada. Silakan edit data yang tersedia.');
        }

        $data = $request->validated();

        $notulensi = DB::transaction(function () use ($rapat, $data) {
            return RapatNotulensi::create([
                'rapat_id' => $rapat->id,
                'notulis_id' => $data['notulis_id'] ?? auth()->id(),
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
                'mode' => $data['mode'],
                'status' => 'draft',
                'judul' => $data['judul'] ?: $rapat->judul,
                'uraian_kegiatan' => $data['uraian_kegiatan'],
                'agenda_rapat' => $data['agenda_rapat'],
                'susunan_agenda' => $data['susunan_agenda'] ?? null,
                'hasil_rapat' => $data['hasil_rapat'],
                'rekomendasi' => $data['rekomendasi'] ?? null,
                'dokumentasi_rapat' => $data['dokumentasi_rapat'] ?? null,
                'catatan' => $data['catatan'] ?? null,
                'approval_ready' => true,
                'submitted_at' => Carbon::now('Asia/Jayapura'),
            ]);
        });

        return redirect()->route('rapat.notulensi.edit', $notulensi)->with('success', 'Notulensi berhasil dibuat.');
    }

    public function uploadFromRapat(UploadRapatNotulensiRequest $request, Rapat $rapat)
    {
        abort_unless(auth()->user()->canAccessMeetingMinutes(), 403);

        $notulensi = $rapat->notulensi;

        if (!$notulensi) {
            $notulensi = RapatNotulensi::create([
                'rapat_id' => $rapat->id,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
                'notulis_id' => auth()->id(),
                'mode' => 'upload',
                'status' => 'draft',
                'judul' => $rapat->judul,
            ]);
        }

        return $this->upload($request, $notulensi);
    }

    public function edit(RapatNotulensi $notulensi)
    {
        abort_unless(auth()->user()->canAccessMeetingMinutes(), 403);

        $notulisOptions = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['notulis', 'operator', 'admin']);
        })->orderBy('hirarki')->orderBy('name')->get();

        $notulensi->load('rapat.kategoriSuratKode', 'rapat.creator', 'notulis');

        return view('rapat.notulensi.form', [
            'rapat' => $notulensi->rapat,
            'notulensi' => $notulensi,
            'notulisOptions' => $notulisOptions,
            'formAction' => route('rapat.notulensi.update', $notulensi),
            'formMethod' => 'PUT',
            'pageTitle' => 'Edit Notulensi',
        ]);
    }

    public function update(StoreRapatNotulensiRequest $request, RapatNotulensi $notulensi)
    {
        abort_unless(auth()->user()->canAccessMeetingMinutes(), 403);

        $data = $request->validated();

        $notulensi->update([
            'notulis_id' => $data['notulis_id'] ?? $notulensi->notulis_id,
            'updated_by' => auth()->id(),
            'mode' => $data['mode'],
            'status' => $notulensi->tidak_membuat_notulen ? 'tanpa_notulen' : 'draft',
            'judul' => $data['judul'] ?: $notulensi->rapat->judul,
            'uraian_kegiatan' => $data['uraian_kegiatan'],
            'agenda_rapat' => $data['agenda_rapat'],
            'susunan_agenda' => $data['susunan_agenda'] ?? null,
            'hasil_rapat' => $data['hasil_rapat'],
            'rekomendasi' => $data['rekomendasi'] ?? null,
            'dokumentasi_rapat' => $data['dokumentasi_rapat'] ?? null,
            'catatan' => $data['catatan'] ?? null,
            'approval_ready' => true,
            'submitted_at' => Carbon::now('Asia/Jayapura'),
        ]);

        return redirect()->route('rapat.notulensi.edit', $notulensi)->with('success', 'Notulensi berhasil diperbarui.');
    }

    public function upload(UploadRapatNotulensiRequest $request, RapatNotulensi $notulensi)
    {
        abort_unless(auth()->user()->canAccessMeetingMinutes(), 403);

        $file = $request->file('notulensi_file');

        if ($notulensi->file_path) {
            Storage::disk('public')->delete($notulensi->file_path);
        }

        $path = $file->store('rapat/notulensi', 'public');

        $notulensi->update([
            'updated_by' => auth()->id(),
            'mode' => 'upload',
            'status' => 'selesai',
            'tidak_membuat_notulen' => false,
            'file_path' => $path,
            'file_nama' => $file->getClientOriginalName(),
            'file_mime' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'catatan' => $request->input('catatan_upload') ?: $notulensi->catatan,
            'approval_ready' => true,
            'submitted_at' => Carbon::now('Asia/Jayapura'),
        ]);

        return redirect()->route('rapat.notulensi.edit', $notulensi)->with('success', 'File notulensi berhasil diupload. Status menjadi selesai.');
    }

    public function skip(Rapat $rapat)
    {
        abort_unless(auth()->user()->canAccessMeetingMinutes(), 403);

        $notulensi = $rapat->notulensi ?: new RapatNotulensi([
            'rapat_id' => $rapat->id,
            'created_by' => auth()->id(),
        ]);

        $notulensi->fill([
            'notulis_id' => auth()->id(),
            'updated_by' => auth()->id(),
            'mode' => 'skip',
            'status' => 'tanpa_notulen',
            'tidak_membuat_notulen' => true,
            'judul' => $rapat->judul,
            'approval_ready' => false,
            'submitted_at' => Carbon::now('Asia/Jayapura'),
        ]);

        if (!$notulensi->exists) {
            $notulensi->save();
        } else {
            $notulensi->save();
        }

        return redirect()->route('rapat.notulensi.index')->with('success', 'Rapat ditandai tanpa notulen dan status selesai.');
    }

    public function pdf(RapatNotulensi $notulensi)
    {
        abort_unless(auth()->user()->canAccessMeetingMinutes(), 403);

        if ($notulensi->mode === 'upload' && $notulensi->file_path && $notulensi->file_mime === 'application/pdf') {
            return response()->file(storage_path('app/public/' . $notulensi->file_path));
        }

        $notulensi->load('rapat.kategoriSuratKode', 'rapat.creator', 'notulis');

        $pdf = PDF::loadView('rapat.notulensi.pdf', [
            'notulensi' => $notulensi,
            'rapat' => $notulensi->rapat,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('notulensi-' . $notulensi->rapat->id . '.pdf');
    }

    public function file(RapatNotulensi $notulensi)
    {
        abort_unless(auth()->user()->canAccessMeetingMinutes(), 403);
        abort_unless($notulensi->file_path, 404);

        return response()->file(storage_path('app/public/' . $notulensi->file_path));
    }
}
