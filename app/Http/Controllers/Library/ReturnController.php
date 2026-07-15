<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;

use App\Library\BookCopy;
use App\Library\Fine;
use App\Library\Loan;
use App\Library\LoanItem;
use App\Library\ReturnModel;
use App\Library\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReturnController extends Controller
{
    public function index(Request $request)
    {
        $query = ReturnModel::with(['loan.member', 'user']);

        if ($request->search) {
            $query->whereHas('loan', fn($q) =>
                $q->where('loan_number', 'like', "%{$request->search}%")
                  ->orWhereHas('member', fn($m) =>
                      $m->where('name', 'like', "%{$request->search}%")
                        ->orWhere('member_number', 'like', "%{$request->search}%")
                  )
            );
        }

        $returns = $query->latest()->paginate(15)->withQueryString();

        return view('library.returns.index', compact('returns'));
    }

    public function create(Request $request)
    {
        $loan = null;
        if ($request->loan_id) {
            $loan = Loan::with(['member', 'loanItems.bookCopy.book', 'loanItems.fine'])
                ->findOrFail($request->loan_id);
        }
        $finePerDay = (float) Setting::get('fine_per_day', 1000);
        return view('library.returns.create', compact('loan', 'finePerDay'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'loan_id'     => 'required|exists:library_loans,id',
            'return_date' => 'required|date',
            'note'        => 'nullable|string',
        ]);

        $loan = Loan::with(['loanItems.bookCopy', 'member'])->findOrFail($validated['loan_id']);

        if ($loan->status === 'dikembalikan') {
            return back()->with('error', 'Peminjaman ini sudah dikembalikan.');
        }

        $finePerDay  = (float) Setting::get('fine_per_day', 1000);
        $returnDate  = Carbon::parse($validated['return_date']);
        $dueDate     = Carbon::parse($loan->due_date);
        $daysLate    = $returnDate->greaterThan($dueDate) ? $dueDate->diffInDays($returnDate) : 0;

        DB::transaction(function () use ($loan, $validated, $returnDate, $daysLate, $finePerDay) {
            // Buat catatan pengembalian
            ReturnModel::create([
                'loan_id'     => $loan->id,
                'user_id'     => auth()->id(),
                'return_date' => $validated['return_date'],
                'note'        => $validated['note'],
            ]);

            // Update status loan
            $loan->update(['status' => 'dikembalikan']);

            // Update tiap item & buat denda jika terlambat
            foreach ($loan->loanItems as $item) {
                $item->update(['returned_at' => $returnDate]);
                $item->bookCopy->update(['status' => 'tersedia']);

                if ($daysLate > 0) {
                    Fine::create([
                        'loan_item_id'   => $item->id,
                        'member_id'      => $loan->member_id,
                        'days_late'      => $daysLate,
                        'amount_per_day' => $finePerDay,
                        'total_amount'   => $daysLate * $finePerDay,
                        'status'         => 'belum_dibayar',
                    ]);
                }
            }
        });

        return redirect()->route('library.returns.index')->with('success', 'Pengembalian berhasil dicatat.' . ($daysLate > 0 ? " Terlambat {$daysLate} hari." : ''));
    }

    public function show(ReturnModel $return)
    {
        $return->load(['loan.member', 'loan.loanItems.bookCopy.book', 'loan.loanItems.fine', 'user']);
        return view('library.returns.show', compact('return'));
    }

    public function searchLoan(Request $request)
    {
        $loans = Loan::with('member')
            ->whereIn('status', ['dipinjam', 'terlambat'])
            ->where(function ($q) use ($request) {
                $q->where('loan_number', 'like', "%{$request->q}%")
                  ->orWhereHas('member', fn($m) =>
                      $m->where('name', 'like', "%{$request->q}%")
                        ->orWhere('member_number', 'like', "%{$request->q}%")
                  );
            })
            ->take(10)
            ->get();

        return response()->json($loans->map(fn($l) => [
            'id'            => $l->id,
            'loan_number'   => $l->loan_number,
            'member_name'   => $l->member->name,
            'member_number' => $l->member->member_number,
            'due_date'      => $l->due_date->format('d/m/Y'),
            'status'        => $l->status,
            'is_overdue'    => $l->isOverdue(),
        ]));
    }
}
