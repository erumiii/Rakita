<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Services\ReservationService;

class ReservationController extends Controller
{
    public function index()
    {
        // Employee role sees every row through RLS.
        $reservations = Reservation::with(['book', 'member'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('employee.reservations.index', compact('reservations'));
    }

    public function destroy(string $reservation, ReservationService $service)
    {
        // Explicit lookup post-middleware (employee RLS context sees all rows).
        $reservation = Reservation::findOrFail($reservation);
        try {
            // Employee context may promote the queue immediately.
            $service->cancel($reservation, promote: true);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['reservation' => $e->getMessage()]);
        }

        return redirect()->route('employee.reservations.index')->with('success', 'Reservation cancelled.');
    }
}
