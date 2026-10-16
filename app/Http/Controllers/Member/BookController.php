<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Reservation;
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

        // Queue sizes are global per book and RLS hides other members' rows,
        // so count them via the superuser connection (counts only, no rows).
        $queueCounts = Reservation::on('pgsql')
            ->whereIn('book_id', $books->pluck('id'))
            ->pending()
            ->selectRaw('book_id, count(*) as aggregate')
            ->groupBy('book_id')
            ->pluck('aggregate', 'book_id');

        return view('member.books.index', compact('books', 'myReservedBookIds', 'myBorrowedBookIds', 'queueCounts'));
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
        $queueCount = Reservation::on('pgsql')->where('book_id', $book->id)->pending()->count();

        return view('member.books.show', compact('book', 'myReservation', 'myActiveBorrow', 'queueCount'));
    }
}
