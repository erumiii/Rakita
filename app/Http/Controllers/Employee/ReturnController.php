<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReturnRequest;
use App\Models\Borrowing;
use App\Services\BorrowingService;

class ReturnController extends Controller
{
    public function create()
    {
        return view('employee.returns.create', [
            'borrowings' => Borrowing::active()->with(['book', 'member'])->orderBy('due_at')->get(),
        ]);
    }

    public function store(StoreReturnRequest $request, BorrowingService $service)
    {
        $borrowing = Borrowing::findOrFail($request->borrowing_id);

        try {
            $service->return($borrowing);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['borrowing_id' => $e->getMessage()]);
        }

        return redirect()->route('employee.borrowings.history')->with('success', 'Pengembalian dicatat.');
    }
}
