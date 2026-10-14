<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function index(Request $request)
    {
        $books = Book::query()
            ->search($request->string('q'))
            ->status($request->string('status'))
            ->orderBy('title')
            ->paginate(15)
            ->withQueryString();

        return view('employee.books.index', compact('books'));
    }

    public function create()
    {
        return view('employee.books.create');
    }

    public function store(StoreBookRequest $request)
    {
        Book::create($request->validated());

        return redirect('/employee/books')->with('success', 'Buku ditambahkan.');
    }

    public function edit(Book $book)
    {
        return view('employee.books.edit', compact('book'));
    }

    public function update(UpdateBookRequest $request, Book $book)
    {
        $book->update($request->validated());

        return redirect('/employee/books')->with('success', 'Buku diperbarui.');
    }

    public function destroy(Book $book)
    {
        if ($book->activeBorrowing()->exists()) {
            return back()->withErrors(['delete' => 'Buku sedang dipinjam dan tidak dapat dihapus.']);
        }

        $book->delete();

        return redirect('/employee/books')->with('success', 'Buku dihapus.');
    }
}
