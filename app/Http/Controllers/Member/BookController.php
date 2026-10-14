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
            ->orderBy('title')
            ->paginate(15)
            ->withQueryString();

        return view('member.books.index', compact('books'));
    }

    public function show(Book $book)
    {
        return view('member.books.show', compact('book'));
    }
}
