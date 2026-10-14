<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBorrowingRequest;
use App\Models\Book;
use App\Models\Borrowing;
use App\Models\User;
use App\Services\BorrowingService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class BorrowController extends Controller
{
    public function create(Request $request)
    {
        // Post-context user reads run as library_app under RLS (Task 6 ruling).
        $members = User::on('pgsql_app')->where('role', 'member')->where('is_active', true)->orderBy('name')->get();
        $books = Book::where('status', 'isAvailable')->orderBy('title')->get();

        $selectedMemberId = $request->integer('member_id');
        $activeCount = $selectedMemberId
            ? Borrowing::where('member_id', $selectedMemberId)->active()->count()
            : null;

        return view('employee.borrowings.create', [
            'members' => $members,
            'books' => $books,
            'selectedMemberId' => $selectedMemberId,
            'activeCount' => $activeCount,
        ]);
    }

    public function store(StoreBorrowingRequest $request, BorrowingService $service)
    {
        // RLS reads must run as library_app; auth lookup stays superuser (Task 6).
        $member = User::on('pgsql_app')->findOrFail($request->member_id);
        $book = Book::findOrFail($request->book_id);

        try {
            $service->borrow($book, $member, (int) $request->borrow_period_days, Carbon::parse($request->borrowed_at));
        } catch (\RuntimeException $e) {
            return back()->withErrors(['book_id' => $e->getMessage()])->withInput();
        }

        return redirect()->route('employee.borrowings.history')->with('success', 'Peminjaman dicatat.');
    }
}
