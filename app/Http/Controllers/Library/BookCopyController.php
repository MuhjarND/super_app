<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;

use App\Library\Book;
use App\Library\BookCopy;
use Illuminate\Http\Request;

class BookCopyController extends Controller
{
    public function index(Request $request)
    {
        $query = BookCopy::with('book');

        if ($request->search) {
            $query->where('copy_code', 'like', "%{$request->search}%")
                  ->orWhereHas('book', fn($q) => $q->where('title', 'like', "%{$request->search}%"));
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->book_id) {
            $query->where('book_id', $request->book_id);
        }

        $copies = $query->latest()->paginate(20)->withQueryString();
        $books  = Book::orderBy('title')->get();

        return view('library.book-copies.index', compact('copies', 'books'));
    }

    public function create(Request $request)
    {
        $books       = Book::orderBy('title')->get();
        $selectedBook = $request->book_id ? Book::find($request->book_id) : null;
        $nextCode    = BookCopy::generateCode();
        return view('library.book-copies.create', compact('books', 'selectedBook', 'nextCode'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'book_id'   => 'required|exists:library_books,id',
            'copy_code' => 'required|string|unique:library_book_copies,copy_code',
            'status'    => 'required|in:tersedia,dipinjam,rusak,hilang',
            'notes'     => 'nullable|string',
            'quantity'  => 'nullable|integer|min:1|max:20',
        ]);

        $quantity = $request->quantity ?? 1;

        if ($quantity > 1) {
            for ($i = 0; $i < $quantity; $i++) {
                BookCopy::create([
                    'book_id'   => $validated['book_id'],
                    'copy_code' => BookCopy::generateCode(),
                    'status'    => $validated['status'],
                    'notes'     => $validated['notes'],
                ]);
            }
        } else {
            BookCopy::create($validated);
        }

        return redirect()->route('library.book-copies.index')->with('success', 'Eksemplar buku berhasil ditambahkan.');
    }

    public function edit(BookCopy $bookCopy)
    {
        $books = Book::orderBy('title')->get();
        return view('library.book-copies.edit', compact('bookCopy', 'books'));
    }

    public function update(Request $request, BookCopy $bookCopy)
    {
        $validated = $request->validate([
            'book_id'   => 'required|exists:library_books,id',
            'copy_code' => 'required|string|unique:library_book_copies,copy_code,' . $bookCopy->id,
            'status'    => 'required|in:tersedia,dipinjam,rusak,hilang',
            'notes'     => 'nullable|string',
        ]);

        $bookCopy->update($validated);

        return redirect()->route('library.book-copies.index')->with('success', 'Data eksemplar berhasil diperbarui.');
    }

    public function destroy(BookCopy $bookCopy)
    {
        if ($bookCopy->status === 'dipinjam') {
            return back()->with('error', 'Tidak dapat menghapus eksemplar yang sedang dipinjam.');
        }

        $bookCopy->delete();

        return redirect()->route('library.book-copies.index')->with('success', 'Eksemplar berhasil dihapus.');
    }

    public function lookup(Request $request)
    {
        $copy = BookCopy::with('book.category', 'book.shelf', 'activeLoanItem.loan.member')
            ->where('copy_code', $request->code)
            ->first();

        if (!$copy) {
            return response()->json(['found' => false, 'message' => 'Kode eksemplar tidak ditemukan.']);
        }

        $loan = null;
        if (auth()->user()->canManageLibraryModule() && $copy->activeLoanItem) {
            $loan = [
                'member_name' => $copy->activeLoanItem->loan->member->name,
                'member_number' => $copy->activeLoanItem->loan->member->member_number,
                'due_date' => $copy->activeLoanItem->loan->due_date->format('d/m/Y'),
            ];
        }

        return response()->json([
            'found'  => true,
            'copy'   => [
                'id'        => $copy->id,
                'copy_code' => $copy->copy_code,
                'status'    => $copy->status,
                'book'      => [
                    'id'       => $copy->book->id,
                    'title'    => $copy->book->title,
                    'author'   => $copy->book->author,
                    'category' => $copy->book->category->name ?? '-',
                    'shelf'    => $copy->book->shelf->name ?? '-',
                    'cover_url' => $copy->book->cover_url,
                ],
                'loan' => $loan,
            ],
        ]);
    }
}
