<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\User;
use App\Support\DbSession;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class MarkOverdueTest extends TestCase
{
    // ponytail: no RefreshDatabase — pgsql_app (library_app) can't see rows
    // inside the default connection's transaction; migrate:fresh per test
    // (same pattern as RlsBootTest/BorrowReturnTest).
    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate:fresh');
        $this->seed(DatabaseSeeder::class);
    }

    private function makeBorrowing(array $attrs): Borrowing
    {
        $employee = User::factory()->employee()->create();
        DbSession::apply($employee);

        return Borrowing::create(array_merge([
            'book_id' => Book::factory()->create()->id,
            'member_id' => User::factory()->member()->create()->id,
            'processed_by' => $employee->id,
            'borrowed_at' => now(),
            'borrow_period_days' => 7,
            'due_at' => now()->addDays(7),
            'status' => 'borrowed',
        ], $attrs));
    }

    public function test_marks_past_due_unreturned_borrowings_overdue(): void
    {
        $overdue = $this->makeBorrowing(['due_at' => now()->subDay()]);
        $current = $this->makeBorrowing(['due_at' => now()->addDay()]);

        $this->artisan('borrowings:mark-overdue')->assertSuccessful();

        $this->assertSame('overdue', $overdue->refresh()->status);
        $this->assertSame('borrowed', $current->refresh()->status);
    }

    public function test_due_not_yet_past_stays_borrowed(): void
    {
        $employee = User::factory()->employee()->create();
        DbSession::apply($employee);
        $user = User::factory()->member()->create();
        $book = Book::factory()->create();
        $b = Borrowing::create([
            'book_id' => $book->id,
            'member_id' => $user->id,
            'processed_by' => $employee->id,
            'borrowed_at' => now(),
            'borrow_period_days' => 7,
            // "Due exactly now" can't be raced against PG's now(); one second
            // future encodes the same strict-< boundary deterministically.
            'due_at' => now()->addSecond(),
            'status' => 'borrowed',
        ]);

        $this->artisan('borrowings:mark-overdue')->assertSuccessful();

        $this->assertSame('borrowed', $b->refresh()->status);
    }

    public function test_returned_rows_never_touched(): void
    {
        $employee = User::factory()->employee()->create();
        DbSession::apply($employee);
        $b = Borrowing::create([
            'book_id' => Book::factory()->create()->id,
            'member_id' => User::factory()->member()->create()->id,
            'processed_by' => $employee->id,
            'borrowed_at' => now()->subDays(10),
            'borrow_period_days' => 7,
            'due_at' => now()->subDays(3),
            'returned_at' => now()->subDay(),
            'status' => 'returned',
        ]);

        $this->artisan('borrowings:mark-overdue')->assertSuccessful();

        $this->assertSame('returned', $b->refresh()->status);
    }

    public function test_idempotent_second_run_changes_nothing(): void
    {
        $b = $this->makeBorrowing(['due_at' => now()->subDay()]);

        $this->artisan('borrowings:mark-overdue')->assertSuccessful();
        $this->artisan('borrowings:mark-overdue')->assertSuccessful();

        $this->assertSame('overdue', $b->refresh()->status);
        $this->assertNull($b->refresh()->returned_at);
    }
}
