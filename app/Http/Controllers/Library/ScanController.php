<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;

use App\Library\BookCopy;
use Illuminate\Http\Request;

class ScanController extends Controller
{
    public function index()
    {
        return view('library.scan.index');
    }

    public function lookup(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        $copy = BookCopy::with(['book.category', 'book.shelf', 'activeLoanItem.loan.member'])
            ->where('copy_code', $request->code)
            ->first();

        if (!$copy) {
            return response()->json([
                'found'   => false,
                'message' => 'Kode eksemplar tidak ditemukan di sistem.',
            ]);
        }

        $loanInfo = null;
        if ($copy->activeLoanItem) {
            $loan = $copy->activeLoanItem->loan;
            $loanInfo = [
                'loan_number'   => $loan->loan_number,
                'member_name'   => $loan->member->name,
                'member_number' => $loan->member->member_number,
                'loan_date'     => $loan->loan_date->format('d/m/Y'),
                'due_date'      => $loan->due_date->format('d/m/Y'),
                'is_overdue'    => $loan->isOverdue(),
            ];
        }

        return response()->json([
            'found' => true,
            'copy'  => [
                'id'        => $copy->id,
                'copy_code' => $copy->copy_code,
                'status'    => $copy->status,
                'book'      => [
                    'id'        => $copy->book->id,
                    'title'     => $copy->book->title,
                    'author'    => $copy->book->author,
                    'publisher' => $copy->book->publisher,
                    'category'  => $copy->book->category->name ?? '-',
                    'shelf'     => $copy->book->shelf->name ?? '-',
                    'cover_url' => $copy->book->cover_url,
                ],
                'loan' => $loanInfo,
            ],
        ]);
    }
}
