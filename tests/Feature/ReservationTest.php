<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\Reservation;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ReservationTest extends TestCase
{
    // Same pattern as BorrowReturnTest: no RefreshDatabase — pgsql_app can't
    // see the default connection's transaction, so migrate:fresh per test.
    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate:fresh');
        $this->seed(DatabaseSeeder::class);
    }

    private function borrowViaEmployee(User $employee, User $member, Book $book): void
    {
        $this->actingAs($employee)->post('/employee/borrowings', [
            'member_id' => $member->id,
            'book_id' => $book->id,
            'borrowed_at' => now()->format('Y-m-d\TH:i'),
            'borrow_period_days' => 7,
        ])->assertRedirect('/employee/borrowings/history');
    }

    public function test_reserve_available_book_gets_pickup_window(): void
    {
        $member = User::factory()->member()->create();
        $book = Book::factory()->create();

        $this->actingAs($member)->post('/member/reservations', ['book_id' => $book->id])
            ->assertRedirect('/member/reservations');

        $r = Reservation::first();
        $this->assertSame('pending', $r->status);
        $this->assertNotNull($r->expires_at);
        $this->assertTrue($r->expires_at->isFuture());
    }

    public function test_reserve_borrowed_book_queues_without_window(): void
    {
        $employee = User::factory()->employee()->create();
        $borrower = User::factory()->member()->create();
        $member = User::factory()->member()->create();
        $book = Book::factory()->create();

        $this->borrowViaEmployee($employee, $borrower, $book);

        $this->actingAs($member)->post('/member/reservations', ['book_id' => $book->id])
            ->assertRedirect('/member/reservations');

        $r = Reservation::first();
        $this->assertSame('pending', $r->status);
        $this->assertNull($r->expires_at);
    }

    public function test_duplicate_pending_reservation_rejected(): void
    {
        $member = User::factory()->member()->create();
        $book = Book::factory()->create();

        $this->actingAs($member)->post('/member/reservations', ['book_id' => $book->id]);
        $this->actingAs($member)->post('/member/reservations', ['book_id' => $book->id])
            ->assertSessionHasErrors('book_id');

        $this->assertSame(1, Reservation::count());
    }

    public function test_cannot_reserve_book_currently_borrowed_by_self(): void
    {
        $employee = User::factory()->employee()->create();
        $member = User::factory()->member()->create();
        $book = Book::factory()->create();

        $this->borrowViaEmployee($employee, $member, $book);

        $this->actingAs($member)->post('/member/reservations', ['book_id' => $book->id])
            ->assertSessionHasErrors('book_id');

        $this->assertSame(0, Reservation::count());
    }

    public function test_max_three_pending_reservations(): void
    {
        $member = User::factory()->member()->create();

        foreach (range(1, 3) as $i) {
            $this->actingAs($member)->post('/member/reservations', ['book_id' => Book::factory()->create()->id]);
        }

        $this->actingAs($member)->post('/member/reservations', ['book_id' => Book::factory()->create()->id])
            ->assertSessionHasErrors('book_id');

        $this->assertSame(3, Reservation::count());
    }

    public function test_return_promotes_queue_head(): void
    {
        $employee = User::factory()->employee()->create();
        $borrower = User::factory()->member()->create();
        $next = User::factory()->member()->create();
        $book = Book::factory()->create();

        $this->borrowViaEmployee($employee, $borrower, $book);
        $this->actingAs($next)->post('/member/reservations', ['book_id' => $book->id]);

        // on('pgsql'): test-process GUC still holds the last request's member
        // context; assertions that span members use the superuser connection.
        $borrowingId = Borrowing::on('pgsql')->first()->id;
        $this->actingAs($employee)->post('/employee/returns', ['borrowing_id' => $borrowingId])
            ->assertRedirect('/employee/borrowings/history');

        $r = Reservation::first();
        $this->assertSame('pending', $r->status);
        $this->assertNotNull($r->expires_at, 'Queue head should get a pickup window on return');
    }

    public function test_borrow_blocked_for_other_member_while_offer_active(): void
    {
        $employee = User::factory()->employee()->create();
        $holder = User::factory()->member()->create();
        $other = User::factory()->member()->create();
        $book = Book::factory()->create();

        // Available book + empty queue → holder gets the pickup window.
        $this->actingAs($holder)->post('/member/reservations', ['book_id' => $book->id]);

        $this->actingAs($employee)->post('/employee/borrowings', [
            'member_id' => $other->id,
            'book_id' => $book->id,
            'borrowed_at' => now()->format('Y-m-d\TH:i'),
            'borrow_period_days' => 7,
        ])->assertSessionHasErrors('book_id');

        $this->assertSame(0, Borrowing::count());
        $this->assertSame('isAvailable', $book->refresh()->status);
    }

    public function test_borrow_by_offer_holder_fulfills_reservation(): void
    {
        $employee = User::factory()->employee()->create();
        $holder = User::factory()->member()->create();
        $book = Book::factory()->create();

        $this->actingAs($holder)->post('/member/reservations', ['book_id' => $book->id]);

        $this->borrowViaEmployee($employee, $holder, $book);

        $this->assertSame('fulfilled', Reservation::first()->status);
        $this->assertSame('isBorrowed', $book->refresh()->status);
    }

    public function test_expire_command_expires_stale_offer_and_promotes_next(): void
    {
        $employee = User::factory()->employee()->create();
        $first = User::factory()->member()->create();
        $second = User::factory()->member()->create();
        $book = Book::factory()->create();

        // First member holds a stale offer; second is queued behind.
        \App\Support\DbSession::apply($employee);
        Reservation::create([
            'book_id' => $book->id,
            'member_id' => $first->id,
            'status' => 'pending',
            'expires_at' => now()->subDay(),
        ]);
        Reservation::create([
            'book_id' => $book->id,
            'member_id' => $second->id,
            'status' => 'pending',
            'expires_at' => null,
        ]);

        Artisan::call('reservations:expire');

        $this->assertSame('expired', Reservation::where('member_id', $first->id)->first()->status);
        $promoted = Reservation::where('member_id', $second->id)->first();
        $this->assertSame('pending', $promoted->status);
        $this->assertNotNull($promoted->expires_at);
    }

    public function test_catalog_confirm_message_ready_for_pickup_on_available_book(): void
    {
        $member = User::factory()->member()->create();
        Book::factory()->create();

        // Available + empty queue: immediate pickup, no queue position shown.
        $this->actingAs($member)->get('/member/books')
            ->assertOk()
            ->assertSee('is ready for pickup')
            ->assertDontSee('You will be on queue');
    }

    public function test_catalog_confirm_message_shows_queue_position(): void
    {
        $employee = User::factory()->employee()->create();
        $borrower = User::factory()->member()->create();
        $first = User::factory()->member()->create();
        $viewer = User::factory()->member()->create();
        $book = Book::factory()->create();

        $this->borrowViaEmployee($employee, $borrower, $book);
        $this->actingAs($first)->post('/member/reservations', ['book_id' => $book->id]);

        // One reservation is queued already, so the viewer would be #2.
        $this->actingAs($viewer)->get('/member/books')
            ->assertOk()
            ->assertSee('You will be on queue: 2');
    }

    public function test_member_can_cancel_own_reservation(): void
    {
        $member = User::factory()->member()->create();
        $book = Book::factory()->create();

        $this->actingAs($member)->post('/member/reservations', ['book_id' => $book->id]);

        $this->actingAs($member)->delete('/member/reservations/'.Reservation::first()->id)
            ->assertRedirect('/member/reservations');

        $this->assertSame('cancelled', Reservation::first()->status);
    }

    public function test_member_cannot_cancel_others_reservation(): void
    {
        $owner = User::factory()->member()->create();
        $intruder = User::factory()->member()->create();
        $book = Book::factory()->create();

        $this->actingAs($owner)->post('/member/reservations', ['book_id' => $book->id]);
        $id = Reservation::on('pgsql')->first()->id;

        // RLS hides the row, so the explicit lookup 404s before any logic runs.
        $this->actingAs($intruder)->delete("/member/reservations/{$id}")
            ->assertNotFound();

        $this->assertSame('pending', Reservation::on('pgsql')->first()->status);
    }

    public function test_member_sees_only_own_reservations(): void
    {
        $memberA = User::factory()->member()->create(['name' => 'Member Alpha']);
        $memberB = User::factory()->member()->create();
        $bookA = Book::factory()->create(['title' => 'Alpha Book Choice']);
        $bookB = Book::factory()->create(['title' => 'Beta Book Choice']);

        $this->actingAs($memberA)->post('/member/reservations', ['book_id' => $bookA->id]);
        $this->actingAs($memberB)->post('/member/reservations', ['book_id' => $bookB->id]);

        $this->actingAs($memberA)->get('/member/reservations')
            ->assertOk()
            ->assertSee('Alpha Book Choice')
            ->assertDontSee('Beta Book Choice');
    }

    public function test_employee_sees_all_reservations(): void
    {
        $employee = User::factory()->employee()->create();
        $memberA = User::factory()->member()->create();
        $memberB = User::factory()->member()->create();

        $this->actingAs($memberA)->post('/member/reservations', ['book_id' => Book::factory()->create(['title' => 'Book From A'])->id]);
        $this->actingAs($memberB)->post('/member/reservations', ['book_id' => Book::factory()->create(['title' => 'Book From B'])->id]);

        $this->actingAs($employee)->get('/employee/reservations')
            ->assertOk()
            ->assertSee('Book From A')
            ->assertSee('Book From B');
    }

    public function test_employee_cancel_promotes_next_in_line(): void
    {
        $employee = User::factory()->employee()->create();
        $first = User::factory()->member()->create();
        $second = User::factory()->member()->create();
        $book = Book::factory()->create();

        $this->actingAs($first)->post('/member/reservations', ['book_id' => $book->id]);
        // First holds the offer; second joins the queue on the now-offered book.
        $this->actingAs($second)->post('/member/reservations', ['book_id' => $book->id]);

        $firstReservationId = Reservation::on('pgsql')->where('member_id', $first->id)->first()->id;
        $this->actingAs($employee)->delete('/employee/reservations/'.$firstReservationId)
            ->assertRedirect('/employee/reservations');

        $promoted = Reservation::where('member_id', $second->id)->first();
        $this->assertSame('pending', $promoted->status);
        $this->assertNotNull($promoted->expires_at, 'Next in line should get the offer after employee cancel');
    }
}
