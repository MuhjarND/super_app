<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;

use App\Library\BookCopy;
use App\Library\Loan;
use App\Library\LoanItem;
use App\Library\Member;
use App\Library\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoanController extends Controller
{
    public function index(Request $request)
    {
        $query = Loan::with(['member', 'loanItems']);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('loan_number', 'like', "%{$request->search}%")
                  ->orWhereHas('member', fn($m) => $m->where('name', 'like', "%{$request->search}%")
                      ->orWhere('member_number', 'like', "%{$request->search}%"));
            });
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->date_from) {
            $query->whereDate('loan_date', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('loan_date', '<=', $request->date_to);
        }

        // Update status ke terlambat jika sudah lewat due_date
        Loan::where('status', 'dipinjam')
            ->where('due_date', '<', Carbon::today())
            ->update(['status' => 'terlambat']);

        $loans = $query->latest()->paginate(15)->withQueryString();

        return view('library.loans.index', compact('loans'));
    }

    public function create()
    {
        $loanDays    = Setting::get('loan_days', 7);
        $maxBooks    = Setting::get('max_books_per_loan', 3);
        return view('library.loans.create', compact('loanDays', 'maxBooks'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'member_id'   => 'required|exists:library_members,id',
            'loan_date'   => 'required|date',
            'due_date'    => 'required|date|after_or_equal:loan_date',
            'note'        => 'nullable|string',
            'copy_codes'  => 'required|array|min:1',
            'copy_codes.*' => 'required|string|exists:library_book_copies,copy_code',
        ]);

        $member = Member::findOrFail($validated['member_id']);
        if ($member->status !== 'aktif') {
            return back()->with('error', 'Anggota tidak aktif, tidak dapat meminjam.');
        }

        // Validasi tiap kode eksemplar
        $copies = [];
        foreach ($validated['copy_codes'] as $code) {
            $copy = BookCopy::where('copy_code', $code)->first();
            if (!$copy || $copy->status !== 'tersedia') {
                return back()->with('error', "Eksemplar {$code} tidak tersedia untuk dipinjam.");
            }
            $copies[] = $copy;
        }

        DB::transaction(function () use ($validated, $copies, $member) {
            $loan = Loan::create([
                'loan_number' => Loan::generateNumber(),
                'member_id'  => $validated['member_id'],
                'user_id'    => auth()->id(),
                'loan_date'  => $validated['loan_date'],
                'due_date'   => $validated['due_date'],
                'status'     => 'dipinjam',
                'note'       => $validated['note'],
            ]);

            foreach ($copies as $copy) {
                LoanItem::create([
                    'loan_id'      => $loan->id,
                    'book_copy_id' => $copy->id,
                ]);
                $copy->update(['status' => 'dipinjam']);
            }
        });

        return redirect()->route('library.loans.index')->with('success', 'Peminjaman berhasil dicatat.');
    }

    public function show(Loan $loan)
    {
        $loan->load(['member', 'user', 'loanItems.bookCopy.book', 'returnRecord', 'loanItems.fine']);
        return view('library.loans.show', compact('loan'));
    }

    public function destroy(Loan $loan)
    {
        if ($loan->status === 'dikembalikan') {
            return back()->with('error', 'Peminjaman yang sudah dikembalikan tidak dapat dihapus.');
        }

        DB::transaction(function () use ($loan) {
            foreach ($loan->loanItems as $item) {
                $item->bookCopy->update(['status' => 'tersedia']);
            }
            $loan->loanItems()->delete();
            $loan->delete();
        });

        return redirect()->route('library.loans.index')->with('success', 'Data peminjaman dihapus.');
    }
}
