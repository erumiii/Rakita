<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Reservation;
use App\Services\ReservationService;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function index(Request $request, ReservationService $service)
    {
        $pending = $request->user()->reservations()
            ->with('book')
            ->pending()
            ->orderBy('created_at')
            ->get();

        $history = $request->user()->reservations()
            ->with('book')
            ->where('status', '!=', 'pending')
            ->orderByDesc('updated_at')
            ->take(10)
            ->get();

        $positions = $pending->mapWithKeys(fn ($r) => [$r->id => $service->queuePosition($r)]);

        return view('member.reservations.index', compact('pending', 'history', 'positions'));
    }

    public function store(Request $request, ReservationService $service)
    {
        $validated = $request->validate([
            'book_id' => 'required|exists:pgsql_app.books,id',
        ]);

        $book = Book::findOrFail($validated['book_id']);

        try {
            $service->reserve($book, $request->user());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['book_id' => $e->getMessage()]);
        }

        app(NotificationService::class)->notifyEmployees('reservation.created', [
            'member' => $request->user()->name,
            'title' => $book->title,
            'link' => '/employee/reservations',
        ]);

        return redirect()->route('member.reservations.index')->with('success', 'Reservation placed.');
    }

    public function destroy(string $reservation, ReservationService $service)
    {
        // Explicit lookup AFTER middleware set the RLS context: a member only
        // resolves their own row (others 404), matching the project's pattern
        // of avoiding implicit binding on RLS-scoped models.
        // Promotion of the next in line is left to the scheduler: member RLS
        // may not touch another member's row.
        $reservation = Reservation::findOrFail($reservation);
        try {
            $service->cancel($reservation);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['reservation' => $e->getMessage()]);
        }

        return redirect()->route('member.reservations.index')->with('success', 'Reservation cancelled.');
    }
}
