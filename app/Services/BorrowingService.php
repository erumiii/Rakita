<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BorrowingService
{
    public const MAX_ACTIVE = 3;
    public const MAX_DAYS = 7;

    public function borrow(Book $book, User $member, int $days, ?Carbon $at = null): Borrowing
    {
        return DB::connection('pgsql_app')->transaction(function () use ($book, $member, $days, $at) {
            $book = Book::whereKey($book->id)->lockForUpdate()->first();

            if ($book->status !== 'isAvailable') {
                throw new \RuntimeException('This book is currently borrowed.');
            }

            // A live pickup offer (expires_at in the future) holds the copy for
            // that member only. Queue state is global per book and RLS hides
            // other members' rows, so read it via the superuser connection.
            $heldForOther = Reservation::on('pgsql')
                ->where('book_id', $book->id)
                ->pending()
                ->where('expires_at', '>', now())
                ->where('member_id', '!=', $member->id)
                ->exists();
            if ($heldForOther) {
                throw new \RuntimeException('This book is reserved for another member.');
            }

            // ponytail: member-row lock backstops the cap race — the unique index
            // guards book_id only; raise it to a member_id index if caps tighten.
            User::on('pgsql_app')->whereKey($member->id)->lockForUpdate()->first();

            $activeCount = Borrowing::where('member_id', $member->id)->active()->count();
            if ($activeCount >= self::MAX_ACTIVE) {
                throw new \RuntimeException('Member already has 3 active borrowings.');
            }

            $at ??= now();

            $borrowing = Borrowing::create([
                'book_id' => $book->id,
                'member_id' => $member->id,
                'processed_by' => auth()->id(),
                'borrowed_at' => $at,
                'borrow_period_days' => $days,
                'due_at' => $at->copy()->addDays($days),
                'status' => 'borrowed',
            ]);

            $book->update(['status' => 'isBorrowed']);

            // Picking up a reserved copy fulfills the member's reservation.
            Reservation::where('book_id', $book->id)
                ->where('member_id', $member->id)
                ->pending()
                ->update(['status' => 'fulfilled']);

            return $borrowing;
        });
    }

    public function return(Borrowing $borrowing, ?Carbon $at = null): Borrowing
    {
        return DB::connection('pgsql_app')->transaction(function () use ($borrowing, $at) {
            $borrowing = Borrowing::whereKey($borrowing->id)->lockForUpdate()->first();

            if ($borrowing->returned_at !== null) {
                throw new \RuntimeException('This borrowing has already been returned.');
            }

            $at ??= now();
            $borrowing->update([
                'returned_at' => $at,
                'status' => 'returned',
            ]);

            $borrowing->book->update(['status' => 'isAvailable']);

            // Copy is back — offer it to the head of the reservation queue.
            (new ReservationService)->promoteNext($borrowing->book->refresh());

            return $borrowing;
        });
    }
}
