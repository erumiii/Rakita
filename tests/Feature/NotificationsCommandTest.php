<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NotificationsCommandTest extends TestCase
{
    // pgsql_app can't see the default connection's transaction — fresh per test.
    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate:fresh');
        $this->seed(DatabaseSeeder::class);
    }

    private function insertBorrowing(Book $book, User $employee, User $member, string $dueAt): void
    {
        DB::connection('pgsql')->table('borrowings')->insert([
            'book_id' => $book->id, 'member_id' => $member->id, 'processed_by' => $employee->id,
            'borrowed_at' => now(), 'borrow_period_days' => 7, 'due_at' => $dueAt,
            'status' => 'borrowed', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_command_generates_reminders_and_is_idempotent(): void
    {
        $employee = User::factory()->employee()->create();
        $member = User::factory()->member()->create();
        $book = Book::factory()->create();

        $this->insertBorrowing($book, $employee, $member, now()->addDays(2));

        Artisan::call('notifications:generate');
        Artisan::call('notifications:generate');

        $db = DB::connection('pgsql');
        $this->assertCount(2, $db->table('notifications')->where('type', 'borrowing.due_soon')->get()); // 1 member + 1 shared employee, no dupes
        $this->assertCount(0, $db->table('notifications')->where('type', 'borrowing.overdue')->get());

        // Past due: command picks it up as overdue on the next run.
        $db->table('borrowings')->update(['due_at' => now()->subDay()]);
        Artisan::call('notifications:generate');

        $this->assertCount(2, $db->table('notifications')->where('type', 'borrowing.overdue')->get());
        // due_soon rows unchanged (dedupe still holds).
        $this->assertCount(2, $db->table('notifications')->where('type', 'borrowing.due_soon')->get());
    }

    public function test_command_generates_pickup_notification(): void
    {
        $member = User::factory()->member()->create();
        $book = Book::factory()->create(['status' => 'isAvailable']);

        DB::connection('pgsql')->table('reservations')->insert([
            'book_id' => $book->id, 'member_id' => $member->id, 'status' => 'pending',
            'expires_at' => now()->addHours(20), 'created_at' => now(), 'updated_at' => now(),
        ]);

        Artisan::call('notifications:generate');
        Artisan::call('notifications:generate');

        $rows = DB::connection('pgsql')->table('notifications')->where('type', 'reservation.pickup')->get();
        $this->assertCount(1, $rows);
        $this->assertSame((string) $member->id, (string) $rows[0]->user_id);
    }
}
