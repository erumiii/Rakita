<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBorrowingRequest;
use App\Models\Book;
use App\Models\User;
use App\Services\BorrowingService;
use Illuminate\Support\Carbon;

class BorrowController extends Controller
{
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

        return redirect()->route('employee.borrowings.history')->with('success', 'Borrowing recorded.');
    }
}
