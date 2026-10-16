<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NotificationsEventTest extends TestCase
{
    // pgsql_app can't see the default connection's transaction — fresh per test.
    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate:fresh');
        $this->seed(DatabaseSeeder::class);
    }

    public function test_reservation_inserts_employee_notification(): void
    {
        $member = User::factory()->member()->create();
        $book = Book::factory()->create(['status' => 'isAvailable']);

        $this->actingAs($member)->post('/member/reservations', ['book_id' => $book->id])
            ->assertRedirect('/member/reservations');

        $row = DB::connection('pgsql')->table('notifications')->where('type', 'reservation.created')->first();
        $this->assertNotNull($row);
        $payload = json_decode($row->payload, true);
        $this->assertSame($member->name, $payload['member']);
        $this->assertSame($book->title, $payload['title']);
        $this->assertSame('/employee/reservations', $payload['link']);
        $this->assertNull($row->user_id);
        $this->assertSame('employee', $row->recipient_role);
    }

    public function test_register_inserts_employee_notification(): void
    {
        $this->post('/register', [
            'name' => 'Jane Doe', 'username' => 'janedoe',
            'password' => 'password', 'password_confirmation' => 'password',
        ])->assertRedirect('/member/books');

        $row = DB::connection('pgsql')->table('notifications')->where('type', 'member.registered')->first();
        $this->assertNotNull($row);
        $payload = json_decode($row->payload, true);
        $this->assertSame('Jane Doe', $payload['name']);
        $this->assertSame('/employee/members', $payload['link']);
        $this->assertNull($row->user_id);
    }

    public function test_book_added_inserts_member_notifications(): void
    {
        $employee = User::factory()->employee()->create();
        $memberA = User::factory()->member()->create();
        User::factory()->member()->create();

        $this->actingAs($employee)->post('/employee/books', [
            'title' => 'Clean Code', 'author' => 'Robert Martin', 'publisher' => 'Prentice Hall',
            'page' => 464, 'isbn' => '9780132350884',
        ])->assertRedirect('/employee/books');

        $rows = DB::connection('pgsql')->table('notifications')->where('type', 'book.added')->get();
        $this->assertCount(2, $rows); // one fan-out row per member
        $payload = json_decode($rows[0]->payload, true);
        $this->assertSame('Clean Code', $payload['title']);
        $this->assertSame('/member/books', $payload['link']);
        $this->assertNotNull($rows[0]->user_id);
        $this->assertSame('isAvailable', Book::where('isbn', '9780132350884')->value('status'));
    }
}
