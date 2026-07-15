<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;

use App\Library\Fine;
use App\Library\Loan;
use App\Library\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $query = Member::query();

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('member_number', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%");
            });
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->gender) {
            $query->where('gender', $request->gender);
        }

        $members = $query->latest()->paginate(15)->withQueryString();

        return view('library.members.index', compact('members'));
    }

    public function create()
    {
        $nextNumber = Member::generateNumber();
        return view('library.members.create', compact('nextNumber'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'member_number'  => 'required|string|unique:library_members,member_number',
            'name'           => 'required|string|max:255',
            'gender'         => 'required|in:L,P',
            'class_position' => 'nullable|string|max:100',
            'address'        => 'nullable|string',
            'phone'          => 'nullable|string|max:20',
            'email'          => 'nullable|email|max:100',
            'photo'          => 'nullable|image|mimes:jpeg,png,jpg|max:1024',
            'status'         => 'required|in:aktif,nonaktif',
            'valid_until'    => 'nullable|date',
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('library/members', 'public');
        }

        Member::create($validated);

        return redirect()->route('library.members.index')->with('success', 'Anggota berhasil didaftarkan.');
    }

    public function show(Member $member)
    {
        $member->load(['loans.loanItems.bookCopy.book', 'fines.loanItem.bookCopy.book']);
        $activeLoans  = $member->loans()->whereIn('status', ['dipinjam', 'terlambat'])->with('loanItems.bookCopy.book')->get();
        $unpaidFines  = $member->fines()->where('status', 'belum_dibayar')->with('loanItem.bookCopy.book')->get();
        $loanHistory  = $member->loans()->latest()->paginate(10);

        return view('library.members.show', compact('member', 'activeLoans', 'unpaidFines', 'loanHistory'));
    }

    public function edit(Member $member)
    {
        return view('library.members.edit', compact('member'));
    }

    public function update(Request $request, Member $member)
    {
        $validated = $request->validate([
            'member_number'  => 'required|string|unique:library_members,member_number,' . $member->id,
            'name'           => 'required|string|max:255',
            'gender'         => 'required|in:L,P',
            'class_position' => 'nullable|string|max:100',
            'address'        => 'nullable|string',
            'phone'          => 'nullable|string|max:20',
            'email'          => 'nullable|email|max:100',
            'photo'          => 'nullable|image|mimes:jpeg,png,jpg|max:1024',
            'status'         => 'required|in:aktif,nonaktif',
            'valid_until'    => 'nullable|date',
        ]);

        if ($request->hasFile('photo')) {
            if ($member->photo) {
                Storage::disk('public')->delete($member->photo);
            }
            $validated['photo'] = $request->file('photo')->store('library/members', 'public');
        }

        $member->update($validated);

        return redirect()->route('library.members.index')->with('success', 'Data anggota berhasil diperbarui.');
    }

    public function destroy(Member $member)
    {
        if ($member->activeLoans()->exists()) {
            return back()->with('error', 'Tidak dapat menghapus anggota yang masih memiliki peminjaman aktif.');
        }

        if ($member->photo) {
            Storage::disk('public')->delete($member->photo);
        }

        $member->delete();

        return redirect()->route('library.members.index')->with('success', 'Anggota berhasil dihapus.');
    }

    public function search(Request $request)
    {
        $members = Member::where('status', 'aktif')
            ->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->q}%")
                  ->orWhere('member_number', 'like', "%{$request->q}%");
            })
            ->take(10)
            ->get(['id', 'member_number', 'name', 'class_position', 'photo', 'status']);

        return response()->json($members->map(fn($m) => [
            'id'            => $m->id,
            'member_number' => $m->member_number,
            'name'          => $m->name,
            'class_position' => $m->class_position,
            'photo_url'     => $m->photo_url,
        ]));
    }
}
