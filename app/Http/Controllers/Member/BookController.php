<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
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

        // Own rows only — RLS-scoped, used to render Reserve button state.
        $myReservedBookIds = $request->user()->reservations()->pending()->pluck('book_id')->all();
        $myBorrowedBookIds = $request->user()->borrowings()->active()->pluck('book_id')->all();

        return view('member.books.index', compact('books', 'myReservedBookIds', 'myBorrowedBookIds'));
    }

    public function show(Request $request, Book $book)
    {
        $myReservation = $request->user()->reservations()
            ->where('book_id', $book->id)
            ->pending()
            ->first();
        $myActiveBorrow = $request->user()->borrowings()
            ->where('book_id', $book->id)
            ->active()
            ->exists();

        return view('member.books.show', compact('book', 'myReservation', 'myActiveBorrow'));
    }
}
