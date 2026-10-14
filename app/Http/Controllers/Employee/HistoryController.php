<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Borrowing;
use App\Models\User;

class HistoryController extends Controller
{
    public function index()
    {
        // Post-context reads run as library_app under RLS (Task 6 ruling).
        return view('employee.borrowings.history', [
            'borrowings' => Borrowing::with(['book' => fn ($q) => $q->withTrashed(), 'member', 'processor'])
                ->orderByDesc('borrowed_at')
                ->paginate(15),
            'members' => User::on('pgsql_app')->where('role', 'member')->where('is_active', true)->orderBy('name')->get(),
            'books' => Book::where('status', 'isAvailable')->orderBy('title')->get(),
        ]);
    }
}
