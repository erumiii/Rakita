<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReturnRequest;
use App\Models\Borrowing;
use App\Services\BorrowingService;
use Illuminate\Support\Carbon;

class ReturnController extends Controller
{
    public function store(StoreReturnRequest $request, BorrowingService $service)
    {
        $borrowing = Borrowing::findOrFail($request->borrowing_id);

        try {
            $service->return($borrowing, $request->filled('returned_at') ? Carbon::parse($request->returned_at) : null);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['borrowing_id' => $e->getMessage()]);
        }

        return redirect()->route('employee.borrowings.history')->with('success', 'Return recorded.');
    }
}
