<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;

use App\Library\Book;
use App\Library\BookCopy;
use App\Library\Fine;
use App\Library\Loan;
use App\Library\Member;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        if (!auth()->user()->canManageLibraryModule()) {
            return redirect()->route('library.books.index');
        }

        $totalBooks   = Book::count();
        $totalCopies  = BookCopy::count();
        $totalMembers = Member::count();
        $activeLoans  = Loan::whereIn('status', ['dipinjam', 'terlambat'])->count();
        $lateLoanCount = Loan::where('status', 'terlambat')
            ->orWhere(function ($q) {
                $q->where('status', 'dipinjam')->where('due_date', '<', Carbon::today());
            })->count();
        $totalFines   = Fine::where('status', 'belum_dibayar')->sum('total_amount');

        // Grafik peminjaman 12 bulan terakhir
        $loanChart = Loan::select(
                DB::raw('MONTH(loan_date) as month'),
                DB::raw('YEAR(loan_date) as year'),
                DB::raw('COUNT(*) as total')
            )
            ->where('loan_date', '>=', Carbon::now()->subMonths(11)->startOfMonth())
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $chartLabels = [];
        $chartData   = [];
        for ($i = 11; $i >= 0; $i--) {
            $date  = Carbon::now()->subMonths($i);
            $label = $date->locale('id')->isoFormat('MMM YYYY');
            $chartLabels[] = $label;
            $found = $loanChart->first(function ($item) use ($date) {
                return $item->month == $date->month && $item->year == $date->year;
            });
            $chartData[] = $found ? $found->total : 0;
        }

        // Peminjaman terbaru
        $recentLoans = Loan::with(['member', 'loanItems.bookCopy.book'])
            ->latest()
            ->take(8)
            ->get();

        // Buku hampir jatuh tempo (3 hari ke depan)
        $dueSoonLoans = Loan::with(['member', 'loanItems.bookCopy.book'])
            ->whereIn('status', ['dipinjam', 'terlambat'])
            ->where('due_date', '<=', Carbon::now()->addDays(3))
            ->orderBy('due_date')
            ->take(5)
            ->get();

        return view('library.dashboard.index', compact(
            'totalBooks', 'totalCopies', 'totalMembers', 'activeLoans',
            'lateLoanCount', 'totalFines', 'chartLabels', 'chartData',
            'recentLoans', 'dueSoonLoans'
        ));
    }
}
