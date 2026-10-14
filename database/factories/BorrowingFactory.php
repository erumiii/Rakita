<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Borrowing>
 */
class BorrowingFactory extends Factory
{
    public function definition(): array
    {
        // ponytail: inserts run as library_app, so the caller must have set
        // RLS context (DbSession::apply) or PostgreSQL inserts will fail.
        return [
            'member_id' => User::factory()->member(),
            'processed_by' => User::factory()->employee(),
            'borrowed_at' => now(),
            'borrow_period_days' => 7,
            'due_at' => fn (array $attrs) => Carbon::parse($attrs['borrowed_at'])->addDays($attrs['borrow_period_days']),
            'status' => 'borrowed',
        ];
    }

    public function configure(): static
    {
        return $this->for(Book::factory());
    }
}
