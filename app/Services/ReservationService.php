<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\Reservation;
use App\Models\User;

class ReservationService
{
    public const MAX_PENDING = 3;
    public const PICKUP_DAYS = 3;

    public function reserve(Book $book, User $member): Reservation
    {
        // The member's own rows — RLS-scoped reads on pgsql_app are fine here.
        $alreadyBorrowing = Borrowing::where('book_id', $book->id)
            ->where('member_id', $member->id)
            ->active()
            ->exists();
        if ($alreadyBorrowing) {
            throw new \RuntimeException('You are currently borrowing this book.');
        }

        $pendingCount = Reservation::where('member_id', $member->id)->pending()->count();
        if ($pendingCount >= self::MAX_PENDING) {
            throw new \RuntimeException('You already have 3 pending reservations.');
        }

        $alreadyReserved = Reservation::where('member_id', $member->id)
            ->where('book_id', $book->id)
            ->pending()
            ->exists();
        if ($alreadyReserved) {
            throw new \RuntimeException('You already have a pending reservation for this book.');
        }

        // The queue is global per book, but RLS hides other members' rows — so
        // queue inspection goes through the superuser connection (same pattern
        // as pre-auth lookups). The insert itself stays RLS-scoped to the member.
        $queueEmpty = ! Reservation::on('pgsql')->where('book_id', $book->id)->pending()->exists();

        return Reservation::create([
            'book_id' => $book->id,
            'member_id' => $member->id,
            'status' => 'pending',
            'expires_at' => $book->status === 'isAvailable' && $queueEmpty
                ? now()->addDays(self::PICKUP_DAYS)
                : null,
        ]);
    }

    /**
     * Cancel a pending reservation. Promotion of the next in line only runs
     * when $promote is true (employee context) — member RLS forbids touching
     * other members' rows, so member cancellations rely on the scheduler.
     */
    public function cancel(Reservation $reservation, bool $promote = false): Reservation
    {
        if ($reservation->status !== 'pending') {
            throw new \RuntimeException('Only a pending reservation can be cancelled.');
        }

        $hadOffer = $reservation->expires_at !== null;
        $reservation->update(['status' => 'cancelled']);

        if ($promote && $hadOffer) {
            $this->promoteNext($reservation->book);
        }

        return $reservation;
    }

    /**
     * Hand the pickup offer to the head of the queue. Caller context must be
     * employee (web) or superuser (scheduler): this updates another member's
     * row, which member RLS deliberately forbids.
     */
    public function promoteNext(Book $book): void
    {
        if ($book->status !== 'isAvailable') {
            return;
        }

        // Clean up stale offers first so the queue keeps moving.
        Reservation::where('book_id', $book->id)
            ->pending()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update(['status' => 'expired']);

        $hasLiveOffer = Reservation::where('book_id', $book->id)->offered()->exists();
        if ($hasLiveOffer) {
            return;
        }

        Reservation::where('book_id', $book->id)
            ->pending()
            ->whereNull('expires_at')
            ->orderBy('created_at')
            ->first()
            ?->update(['expires_at' => now()->addDays(self::PICKUP_DAYS)]);
    }

    /** 1-based position in the book's queue (1 = next in line). */
    public function queuePosition(Reservation $reservation): int
    {
        // Cross-member queue count — global per book, read via superuser conn.
        return Reservation::on('pgsql')
            ->where('book_id', $reservation->book_id)
            ->pending()
            ->where('created_at', '<=', $reservation->created_at)
            ->count();
    }
}
