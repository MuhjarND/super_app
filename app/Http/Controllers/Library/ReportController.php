<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;

use App\Library\Book;
use App\Library\Fine;
use App\Library\Loan;
use App\Library\LoanItem;
use App\Library\Member;
use App\Library\ReturnModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade as PDF;

class ReportController extends Controller
{
    public function index()
    {
        return view('library.reports.index');
    }

    public function books(Request $request)
    {
        $query = Book::with(['category', 'shelf'])->withCount('copies');
        if ($request->category_id) $query->where('category_id', $request->category_id);
        $books = $query->orderBy('title')->get();

        if ($request->export === 'pdf') {
            $pdf = PDF::loadView('library.reports.pdf.books', compact('books'))
                ->setPaper('a4', 'landscape');
            return $pdf->download('laporan-buku-' . now()->format('Ymd') . '.pdf');
        }

        return view('library.reports.books', compact('books'));
    }

    public function members(Request $request)
    {
        $query = Member::withCount(['loans', 'activeLoans']);
        if ($request->status) $query->where('status', $request->status);
        $members = $query->orderBy('name')->get();

        if ($request->export === 'pdf') {
            $pdf = PDF::loadView('library.reports.pdf.members', compact('members'))
                ->setPaper('a4', 'portrait');
            return $pdf->download('laporan-anggota-' . now()->format('Ymd') . '.pdf');
        }

        return view('library.reports.members', compact('members'));
    }

    public function loans(Request $request)
    {
        $query = Loan::with(['member', 'loanItems.bookCopy.book', 'user']);
        if ($request->date_from) $query->whereDate('loan_date', '>=', $request->date_from);
        if ($request->date_to)   $query->whereDate('loan_date', '<=', $request->date_to);
        if ($request->status)    $query->where('status', $request->status);
        $loans = $query->latest('loan_date')->get();

        if ($request->export === 'pdf') {
            $pdf = PDF::loadView('library.reports.pdf.loans', compact('loans'))
                ->setPaper('a4', 'landscape');
            return $pdf->download('laporan-peminjaman-' . now()->format('Ymd') . '.pdf');
        }

        return view('library.reports.loans', compact('loans'));
    }

    public function returns(Request $request)
    {
        $query = ReturnModel::with(['loan.member', 'loan.loanItems.bookCopy.book', 'user']);
        if ($request->date_from) $query->whereDate('return_date', '>=', $request->date_from);
        if ($request->date_to)   $query->whereDate('return_date', '<=', $request->date_to);
        $returns = $query->latest('return_date')->get();

        if ($request->export === 'pdf') {
            $pdf = PDF::loadView('library.reports.pdf.returns', compact('returns'))
                ->setPaper('a4', 'landscape');
            return $pdf->download('laporan-pengembalian-' . now()->format('Ymd') . '.pdf');
        }

        return view('library.reports.returns', compact('returns'));
    }

    public function lates(Request $request)
    {
        $query = Loan::with(['member', 'loanItems.bookCopy.book'])
            ->where(function ($q) {
                $q->where('status', 'terlambat')
                  ->orWhere(fn($q2) => $q2->where('status', 'dipinjam')->where('due_date', '<', Carbon::today()));
            });
        if ($request->date_from) $query->whereDate('due_date', '>=', $request->date_from);
        if ($request->date_to)   $query->whereDate('due_date', '<=', $request->date_to);
        $loans = $query->orderBy('due_date')->get();

        if ($request->export === 'pdf') {
            $pdf = PDF::loadView('library.reports.pdf.lates', compact('loans'))
                ->setPaper('a4', 'landscape');
            return $pdf->download('laporan-keterlambatan-' . now()->format('Ymd') . '.pdf');
        }

        return view('library.reports.lates', compact('loans'));
    }

    public function fines(Request $request)
    {
        $query = Fine::with(['member', 'loanItem.bookCopy.book', 'paidByUser']);
        if ($request->status) $query->where('status', $request->status);
        if ($request->date_from) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->date_to)   $query->whereDate('created_at', '<=', $request->date_to);
        $fines = $query->latest()->get();

        if ($request->export === 'pdf') {
            $pdf = PDF::loadView('library.reports.pdf.fines', compact('fines'))
                ->setPaper('a4', 'portrait');
            return $pdf->download('laporan-denda-' . now()->format('Ymd') . '.pdf');
        }

        return view('library.reports.fines', compact('fines'));
    }
}
