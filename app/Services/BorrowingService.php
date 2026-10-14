<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BorrowingService
{
    public const MAX_ACTIVE = 3;
    public const MAX_DAYS = 7;

    public function borrow(Book $book, User $member, int $days, ?Carbon $at = null): Borrowing
    {
        return DB::transaction(function () use ($book, $member, $days, $at) {
            $book = Book::whereKey($book->id)->lockForUpdate()->first();

            if ($book->status !== 'isAvailable') {
                throw new \RuntimeException('Buku sedang dipinjam.');
            }

            $activeCount = Borrowing::where('member_id', $member->id)->active()->count();
            if ($activeCount >= self::MAX_ACTIVE) {
                throw new \RuntimeException('Member sudah meminjam 3 buku.');
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

            return $borrowing;
        });
    }

    public function return(Borrowing $borrowing, ?Carbon $at = null): Borrowing
    {
        return DB::transaction(function () use ($borrowing, $at) {
            $borrowing = Borrowing::whereKey($borrowing->id)->lockForUpdate()->first();

            if ($borrowing->returned_at !== null) {
                throw new \RuntimeException('Peminjaman sudah dikembalikan.');
            }

            $at ??= now();
            $borrowing->update([
                'returned_at' => $at,
                'status' => 'returned',
            ]);

            $borrowing->book->update(['status' => 'isAvailable']);

            return $borrowing;
        });
    }
}
