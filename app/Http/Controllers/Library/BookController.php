<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;

use App\Library\Book;
use App\Library\Category;
use App\Library\Shelf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

        return view('library.books.index', compact('books', 'categories', 'shelves'));
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

        Book::create($validated);

        return redirect()->route('library.books.index')->with('success', 'Buku berhasil ditambahkan.');
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

        if ($book->cover) {
            Storage::disk('public')->delete($book->cover);
        }

        $book->delete();

        return redirect()->route('library.books.index')->with('success', 'Buku berhasil dihapus.');
    }
}
