<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Borrowing;

class HistoryController extends Controller
{
    public function index()
    {
        return view('employee.borrowings.history', [
            'borrowings' => Borrowing::with(['book' => fn ($q) => $q->withTrashed(), 'member', 'processor'])
                ->orderByDesc('borrowed_at')
                ->paginate(15),
        ]);
    }
}
