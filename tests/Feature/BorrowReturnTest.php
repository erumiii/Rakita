<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class BorrowReturnTest extends TestCase
{
    // ponytail: no RefreshDatabase — pgsql_app (library_app) can't see rows
    // inside the default connection's transaction; migrate:fresh per test
    // (same pattern as RlsBootTest).
    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate:fresh');
        $this->seed(DatabaseSeeder::class);
    }

    private function borrowData(int $memberId, int $bookId, int $days = 7): array
    {
        return [
            'member_id' => $memberId,
            'book_id' => $bookId,
            'borrowed_at' => '2026-10-14T10:00',
            'borrow_period_days' => $days,
        ];
    }

    public function test_borrow_success_flips_status_and_sets_fields(): void
    {
        $employee = User::factory()->employee()->create();
        $member = User::factory()->member()->create();
        $book = Book::factory()->create();

        $this->actingAs($employee)->post('/employee/borrowings', $this->borrowData($member->id, $book->id))
            ->assertRedirect('/employee/borrowings/history');

        $this->assertSame('isBorrowed', $book->refresh()->status);

        $b = Borrowing::first();
        $this->assertSame($member->id, $b->member_id);
        $this->assertSame($employee->id, $b->processed_by);
        $this->assertSame('borrowed', $b->status);
        $this->assertTrue($b->due_at->equalTo(\Illuminate\Support\Carbon::parse('2026-10-21 10:00', 'Asia/Jakarta')));
    }

    public function test_borrow_rejected_when_book_is_borrowed(): void
    {
        $employee = User::factory()->employee()->create();
        $member = User::factory()->member()->create();
        $book = Book::factory()->create(['status' => 'isBorrowed']);

        $this->actingAs($employee)->post('/employee/borrowings', $this->borrowData($member->id, $book->id))
            ->assertSessionHasErrors('book_id');

        $this->assertSame(0, Borrowing::count());
        $this->assertSame('isBorrowed', $book->refresh()->status);
    }

    public function test_borrow_rejected_when_member_already_has_three_active_borrows(): void
    {
        $employee = User::factory()->employee()->create();
        $member = User::factory()->member()->create();
        $book = Book::factory()->create();

        // Direct model creates run as library_app with no context (RLS insert
        // policy needs the employee role), so set the session vars first.
        \App\Support\DbSession::apply($employee);
        foreach (range(1, 3) as $i) {
            Borrowing::create([
                'book_id' => Book::factory()->create()->id,
                'member_id' => $member->id,
                'processed_by' => $employee->id,
                'borrowed_at' => now(),
                'borrow_period_days' => 7,
                'due_at' => now()->addDays(7),
                'status' => 'borrowed',
            ]);
        }

        $this->actingAs($employee)->post('/employee/borrowings', $this->borrowData($member->id, $book->id))
            ->assertSessionHasErrors('book_id');

        $this->assertSame(3, Borrowing::count());
        $this->assertSame('isAvailable', $book->refresh()->status);
    }

    public function test_borrow_rejected_when_period_out_of_bounds(): void
    {
        $employee = User::factory()->employee()->create();
        $member = User::factory()->member()->create();
        $book = Book::factory()->create();

        foreach ([0, 8] as $days) {
            $this->actingAs($employee)->post('/employee/borrowings', $this->borrowData($member->id, $book->id, $days))
                ->assertSessionHasErrors('borrow_period_days');
        }

        $this->assertSame(0, Borrowing::count());
    }

    public function test_return_sets_returned_at_status_and_book_available(): void
    {
        $employee = User::factory()->employee()->create();
        $member = User::factory()->member()->create();
        $book = Book::factory()->create();

        $this->actingAs($employee)->post('/employee/borrowings', $this->borrowData($member->id, $book->id))
            ->assertRedirect('/employee/borrowings/history');

        $this->actingAs($employee)->post('/employee/returns', ['borrowing_id' => Borrowing::first()->id])
            ->assertRedirect('/employee/borrowings/history');

        $b = Borrowing::first();
        $this->assertNotNull($b->returned_at);
        $this->assertSame('returned', $b->status);
        $this->assertSame('isAvailable', $book->refresh()->status);
    }

    public function test_double_borrow_race_exactly_one_succeeds(): void
    {
        $employee = User::factory()->employee()->create();
        $member = User::factory()->member()->create();
        $book = Book::factory()->create();

        // Two queued posts on the same book: the second post's locked re-read
        // must see the flip and reject (lockForUpdate + autocommit).
        $this->actingAs($employee)->post('/employee/borrowings', $this->borrowData($member->id, $book->id))
            ->assertRedirect('/employee/borrowings/history');

        $this->actingAs($employee)->post('/employee/borrowings', $this->borrowData($member->id, $book->id))
            ->assertSessionHasErrors('book_id');

        $this->assertSame(1, Borrowing::count());
    }

    public function test_delete_blocked_while_book_borrowed(): void
    {
        $employee = User::factory()->employee()->create();
        $member = User::factory()->member()->create();
        $book = Book::factory()->create();

        $this->actingAs($employee)->post('/employee/borrowings', $this->borrowData($member->id, $book->id))
            ->assertRedirect('/employee/borrowings/history');

        $this->actingAs($employee)->delete("/employee/books/{$book->id}")
            ->assertRedirect()
            ->assertSessionHasErrors('delete');

        $this->assertNull($book->refresh()->deleted_at);
    }
}
