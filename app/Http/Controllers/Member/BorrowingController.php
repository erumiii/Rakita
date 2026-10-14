<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BorrowingController extends Controller
{
    public function index(Request $request)
    {
        $active = $request->user()->borrowings()
            ->with(['book' => fn ($q) => $q->withTrashed()])
            ->active()
            ->orderBy('due_at')
            ->get();

        $returned = $request->user()->borrowings()
            ->with(['book' => fn ($q) => $q->withTrashed()])
            ->where('status', 'returned')
            ->orderByDesc('returned_at')
            ->take(10)
            ->get();

        return view('member.borrowings.index', compact('active', 'returned'));
    }
}
