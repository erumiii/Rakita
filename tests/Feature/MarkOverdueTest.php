<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\User;
use App\Support\DbSession;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
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

    private function makeBorrowing(array $attrs = []): Borrowing
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
        // ponytail: default timestamp(0) rounds due_at to whole seconds, so
        // due_at = now() can never equal PG's microsecond transaction_timestamp;
        // widen in-test to pin exact equality. Move to the migration if
        // sub-second due_at ever becomes real.
        DB::statement('ALTER TABLE borrowings ALTER COLUMN due_at TYPE timestamp(6)');

        $b = $this->makeBorrowing();

        // PG now() is transaction_timestamp: frozen for this tx, and the
        // command joins it on the default connection, so due_at == the
        // command's now() exactly — a `<=` implementation would mark overdue.
        DB::transaction(function () use ($b) {
            DB::update('UPDATE borrowings SET due_at = now() WHERE id = ?', [$b->id]);
            $this->artisan('borrowings:mark-overdue')->assertSuccessful();
        });

        $this->assertSame('borrowed', $b->refresh()->status);
    }

    public function test_returned_rows_never_touched(): void
    {
        $b = $this->makeBorrowing([
            'borrowed_at' => now()->subDays(10),
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
