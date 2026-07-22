<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;

use App\Library\Book;
use App\Library\Category;
use App\Library\Shelf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class BookController extends Controller
{
    public function index(Request $request)
    {
        $query = Book::with(['category', 'shelf', 'copies'])->withCount(['copies']);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                  ->orWhere('author', 'like', "%{$request->search}%")
                  ->orWhere('isbn', 'like', "%{$request->search}%");
            });
        }
        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->shelf_id) {
            $query->where('shelf_id', $request->shelf_id);
        }

        $books      = $query->latest()->paginate(15)->withQueryString();
        $categories = Category::orderBy('name')->get();
        $shelves    = Shelf::orderBy('name')->get();

        return response()
            ->view('library.books.index', compact('books', 'categories', 'shelves'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();
        $shelves    = Shelf::orderBy('name')->get();
        return view('library.books.create', compact('categories', 'shelves'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'author'      => 'required|string|max:255',
            'publisher'   => 'nullable|string|max:255',
            'year'        => 'nullable|digits:4|integer',
            'isbn'        => 'nullable|string|max:20|unique:library_books,isbn',
            'category_id' => 'required|exists:library_categories,id',
            'shelf_id'    => 'nullable|exists:library_shelves,id',
            'description' => 'nullable|string',
            'cover'       => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($request->hasFile('cover')) {
            $validated['cover'] = $request->file('cover')->store('library/books', 'public');
        }

        $book = DB::transaction(function () use ($validated) {
            return Book::create($validated);
        });

        return redirect()
            ->route('library.books.index', ['book_refresh' => now()->timestamp, 'highlight' => $book->id])
            ->with('success', 'Buku berhasil ditambahkan.');
    }

    public function show(Book $book)
    {
        $book->load(['category', 'shelf', 'copies.loanItems.loan.member']);
        return view('library.books.show', compact('book'));
    }

    public function edit(Book $book)
    {
        $categories = Category::orderBy('name')->get();
        $shelves    = Shelf::orderBy('name')->get();
        return view('library.books.edit', compact('book', 'categories', 'shelves'));
    }

    public function update(Request $request, Book $book)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'author'      => 'required|string|max:255',
            'publisher'   => 'nullable|string|max:255',
            'year'        => 'nullable|digits:4|integer',
            'isbn'        => 'nullable|string|max:20|unique:library_books,isbn,' . $book->id,
            'category_id' => 'required|exists:library_categories,id',
            'shelf_id'    => 'nullable|exists:library_shelves,id',
            'description' => 'nullable|string',
            'cover'       => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($request->hasFile('cover')) {
            if ($book->cover) {
                Storage::disk('public')->delete($book->cover);
            }
            $validated['cover'] = $request->file('cover')->store('library/books', 'public');
        }

        $book->update($validated);

        return redirect()->route('library.books.index')->with('success', 'Data buku berhasil diperbarui.');
    }

    public function destroy(Book $book)
    {
        if ($book->copies()->whereIn('status', ['dipinjam'])->exists()) {
            return back()->with('error', 'Tidak dapat menghapus buku yang sedang dipinjam.');
        }

        $cover = $book->cover;

        try {
            DB::transaction(function () use ($book) {
                $lockedBook = Book::query()->lockForUpdate()->findOrFail($book->getKey());

                if ($lockedBook->copies()->where('status', 'dipinjam')->exists()) {
                    throw new \RuntimeException('Buku sedang dipinjam dan tidak dapat dihapus.');
                }

                $lockedBook->delete();
            });
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', $exception->getMessage() === 'Buku sedang dipinjam dan tidak dapat dihapus.'
                ? $exception->getMessage()
                : 'Buku gagal dihapus. Silakan coba kembali.');
        }

        if ($cover) {
            Storage::disk('public')->delete($cover);
        }

        if (Book::query()->whereKey($book->getKey())->exists()) {
            return back()->with('error', 'Buku belum terhapus. Silakan coba kembali.');
        }

        return redirect()
            ->route('library.books.index', ['book_refresh' => now()->timestamp])
            ->with('success', 'Buku berhasil dihapus.');
    }
}
