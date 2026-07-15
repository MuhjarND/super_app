<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;

use App\Library\Fine;
use Illuminate\Http\Request;

class FineController extends Controller
{
    public function index(Request $request)
    {
        $query = Fine::with(['member', 'loanItem.bookCopy.book', 'loanItem.loan']);

        if ($request->search) {
            $query->whereHas('member', fn($q) =>
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('member_number', 'like', "%{$request->search}%")
            );
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $fines      = $query->latest()->paginate(15)->withQueryString();
        $totalUnpaid = Fine::where('status', 'belum_dibayar')->sum('total_amount');
        $totalPaid   = Fine::where('status', 'lunas')->sum('total_amount');

        return view('library.fines.index', compact('fines', 'totalUnpaid', 'totalPaid'));
    }

    public function show(Fine $fine)
    {
        $fine->load(['member', 'loanItem.bookCopy.book', 'loanItem.loan.member', 'paidByUser']);
        return view('library.fines.show', compact('fine'));
    }

    public function pay(Fine $fine)
    {
        if ($fine->status === 'lunas') {
            return back()->with('error', 'Denda ini sudah lunas.');
        }

        $fine->update([
            'status'  => 'lunas',
            'paid_at' => now(),
            'paid_by' => auth()->id(),
        ]);

        return back()->with('success', 'Denda berhasil ditandai lunas.');
    }

    public function payAll(Request $request)
    {
        $validated = $request->validate([
            'member_id' => 'required|exists:library_members,id',
        ]);

        Fine::where('member_id', $validated['member_id'])
            ->where('status', 'belum_dibayar')
            ->update([
                'status'  => 'lunas',
                'paid_at' => now(),
                'paid_by' => auth()->id(),
            ]);

        return back()->with('success', 'Semua denda berhasil dilunasi.');
    }
}
