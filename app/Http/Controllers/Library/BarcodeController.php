<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;

use App\Library\BookCopy;
use Illuminate\Http\Request;
use Picqer\Barcode\BarcodeGeneratorSVG;

class BarcodeController extends Controller
{
    public function index(Request $request)
    {
        $copies = BookCopy::with('book')
            ->when($request->search, fn($q) =>
                $q->where('copy_code', 'like', "%{$request->search}%")
                  ->orWhereHas('book', fn($b) => $b->where('title', 'like', "%{$request->search}%"))
            )
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('library.barcode.index', compact('copies'));
    }

    public function show(BookCopy $bookCopy)
    {
        $generator = new BarcodeGeneratorSVG();
        $barcode   = $generator->getBarcode($bookCopy->copy_code, $generator::TYPE_CODE_128, 2, 60);

        return view('library.barcode.show', compact('bookCopy', 'barcode'));
    }

    public function print(Request $request)
    {
        $ids = $request->ids ? explode(',', $request->ids) : [];

        if (empty($ids)) {
            return back()->with('error', 'Pilih minimal satu eksemplar untuk dicetak.');
        }

        $copies    = BookCopy::with('book')->whereIn('id', $ids)->get();
        $generator = new BarcodeGeneratorSVG();
        $barcodes  = [];

        foreach ($copies as $copy) {
            $barcodes[$copy->id] = $generator->getBarcode(
                $copy->copy_code,
                $generator::TYPE_CODE_128,
                2,
                50
            );
        }

        return view('library.barcode.print', compact('copies', 'barcodes'));
    }

    public function generateSvg(BookCopy $bookCopy)
    {
        $generator = new BarcodeGeneratorSVG();
        $svg = $generator->getBarcode($bookCopy->copy_code, $generator::TYPE_CODE_128, 2, 60);

        return response($svg)->header('Content-Type', 'image/svg+xml');
    }
}
