<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use App\Services\NotificationService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NotificationsServiceTest extends TestCase
{
    // pgsql_app can't see the default connection's transaction — fresh per test.
    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate:fresh');
        $this->seed(DatabaseSeeder::class);
    }

    private function actAsEmployeeContext(): void
    {
        // Inserts go through the superuser conn; this only keeps web-context
        // queries (RLS reads) behaving like an employee session.
        DB::connection('pgsql_app')->statement("SELECT set_config('app.current_role', 'employee', false)");
    }

    public function test_notify_employees_inserts_shared_row(): void
    {
        $employee = User::factory()->employee()->create();
        $this->actingAs($employee)->get('/employee/books');

        app(NotificationService::class)->notifyEmployees('member.registered', [
            'name' => 'Jane Doe', 'link' => '/employee/members',
        ]);

        $row = DB::connection('pgsql_app')->table('notifications')->first();
        $this->assertSame('member.registered', $row->type);
        $this->assertNull($row->user_id);
        $this->assertSame('employee', $row->recipient_role);
    }

    public function test_notify_member_inserts_own_row(): void
    {
        $member = User::factory()->member()->create();
        $this->actingAs($member)->get('/member/books');

        app(NotificationService::class)->notify($member->id, null, 'book.added', [
            'title' => 'Clean Code', 'link' => '/member/books',
        ]);

        $row = DB::connection('pgsql_app')->table('notifications')->first();
        $this->assertSame((string) $member->id, (string) $row->user_id);
    }

    public function test_due_soon_is_idempotent_one_row_per_borrowing(): void
    {
        $employee = User::factory()->employee()->create();
        $member = User::factory()->member()->create();
        $book = Book::factory()->create();
        $this->actingAs($employee)->post('/employee/borrowings', [
            'member_id' => $member->id, 'book_id' => $book->id,
            'borrowed_at' => now()->format('Y-m-d\TH:i'), 'borrow_period_days' => 7,
        ])->assertRedirect();

        // Pull the due date into the 3-day window.
        DB::connection('pgsql')->table('borrowings')->update(['due_at' => now()->addDays(2)]);
        $this->actAsEmployeeContext();

        $service = app(NotificationService::class);
        $service->dueSoon();
        $service->dueSoon();

        $memberRows = DB::connection('pgsql')->table('notifications')->where('type', 'borrowing.due_soon')->whereNotNull('user_id')->get();
        $employeeRows = DB::connection('pgsql')->table('notifications')->where('type', 'borrowing.due_soon')->whereNull('user_id')->get();
        $this->assertCount(1, $memberRows);
        $this->assertCount(1, $employeeRows);
        $this->assertSame((string) $member->id, (string) $memberRows[0]->user_id);
        $this->assertSame($book->title, json_decode($memberRows[0]->payload, true)['title']);
    }

    public function test_overdue_only_for_past_due(): void
    {
        $employee = User::factory()->employee()->create();
        $member = User::factory()->member()->create();
        $futureBook = Book::factory()->create();
        $pastBook = Book::factory()->create();

        // Two borrowings: one due in 5 days (outside window), one due yesterday.
        $this->actingAs($employee)->post('/employee/borrowings', [
            'member_id' => $member->id, 'book_id' => $futureBook->id,
            'borrowed_at' => now()->format('Y-m-d\TH:i'), 'borrow_period_days' => 7,
        ])->assertRedirect();
        $this->actingAs($employee)->post('/employee/borrowings', [
            'member_id' => $member->id, 'book_id' => $pastBook->id,
            'borrowed_at' => now()->format('Y-m-d\TH:i'), 'borrow_period_days' => 7,
        ])->assertRedirect();

        $db = DB::connection('pgsql');
        $db->table('borrowings')->where('book_id', $futureBook->id)->update(['due_at' => now()->addDays(5)]);
        $db->table('borrowings')->where('book_id', $pastBook->id)->update(['due_at' => now()->subDay()]);
        $this->actAsEmployeeContext();

        $service = app(NotificationService::class);
        $service->overdue();
        $service->overdue();

        $rows = $db->table('notifications')->where('type', 'borrowing.overdue')->get();
        $this->assertCount(2, $rows); // 1 member row + 1 shared employee row, for the past-due book only
        foreach ($rows as $row) {
            $this->assertSame($pastBook->title, json_decode($row->payload, true)['title']);
        }
    }

    public function test_pickup_ready_for_live_offers(): void
    {
        $employee = User::factory()->employee()->create();
        $member = User::factory()->member()->create();
        $book = Book::factory()->create(['status' => 'isAvailable']);

        $this->actingAs($member)->post('/member/reservations', ['book_id' => $book->id])
            ->assertRedirect('/member/reservations');

        // Reservation was created with a live offer (expires_at future). Drop member
        // context so the service can also see cross-member rows via set role below.
        $this->actAsEmployeeContext();

        $service = app(NotificationService::class);
        $service->pickupReady();
        $service->pickupReady();

        $rows = DB::connection('pgsql')->table('notifications')->where('type', 'reservation.pickup')->get();
        $this->assertCount(1, $rows);
        $this->assertSame((string) $member->id, (string) $rows[0]->user_id);
        $payload = json_decode($rows[0]->payload, true);
        $this->assertSame($book->title, $payload['title']);
        $this->assertSame('/member/reservations', $payload['link']);
    }
}
