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

class MemberPortalTest extends TestCase
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

    private function makeBorrowing(User $member, array $attrs = []): Borrowing
    {
        // Factory inserts run as library_app; employee context satisfies RLS.
        DbSession::apply(User::factory()->employee()->create());

        return Borrowing::factory()->create(['member_id' => $member->id] + $attrs);
    }

    public function test_member_sees_collection_with_statuses_and_search_filters(): void
    {
        $member = User::factory()->member()->create();
        Book::factory()->create(['title' => 'Laravel Deep Dive', 'status' => 'isAvailable']);
        Book::factory()->create(['title' => 'PHP Patterns', 'status' => 'isBorrowed']);

        $response = $this->actingAs($member)->get('/member/books');

        $response->assertOk();
        $response->assertSee('Laravel Deep Dive');
        $response->assertSee('PHP Patterns');
        $response->assertSee('isAvailable');
        $response->assertSee('isBorrowed');

        $response = $this->actingAs($member)->get('/member/books?q=Laravel');

        $response->assertOk();
        $response->assertSee('Laravel Deep Dive');
        $response->assertDontSee('PHP Patterns');
    }

    public function test_member_book_detail_shows_fields(): void
    {
        $member = User::factory()->member()->create();
        $book = Book::factory()->create(['title' => 'Detail Target', 'author' => 'Jane Author']);

        $this->actingAs($member)->get("/member/books/{$book->id}")
            ->assertOk()
            ->assertSee('Detail Target')
            ->assertSee('Jane Author');
    }

    public function test_member_sees_only_own_borrowings_with_reminder(): void
    {
        $member = User::factory()->member()->create();
        $other = User::factory()->member()->create();
        $mine = $this->makeBorrowing($member, ['due_at' => now()->addDays(2)]);
        $othersBorrowing = $this->makeBorrowing($other);
        $returned = $this->makeBorrowing($member, [
            'returned_at' => now(),
            'status' => 'returned',
        ]);
        $othersTitle = $othersBorrowing->book->title;

        $response = $this->actingAs($member)->get('/member/borrowings');

        $response->assertOk();
        $response->assertSee($mine->book->title);
        $response->assertSee('>2 days left<', false);
        $response->assertSee($returned->book->title); // returned section below
        $response->assertDontSee($othersTitle);
    }

    public function test_overdue_borrowing_shows_terlambat_badge(): void
    {
        $member = User::factory()->member()->create();
        $this->makeBorrowing($member, [
            'due_at' => now()->subDays(3),
            'status' => 'overdue',
        ]);

        $response = $this->actingAs($member)->get('/member/borrowings');

        $response->assertOk();
        $response->assertSee('>Overdue by 3 days<', false);
        $response->assertSee('overdue', false);
    }

    public function test_enforces_rls_across_members(): void
    {
        $memberA = User::factory()->member()->create();
        $memberB = User::factory()->member()->create();
        $employee = User::factory()->employee()->create();
        $book = Book::factory()->create();

        DbSession::apply($employee);
        $borrowing = Borrowing::factory()->for($book)->create([
            'member_id' => $memberB->id,
            'processed_by' => $employee->id,
        ]);

        $conn = DB::connection('pgsql_app');
        $conn->statement("SELECT set_config('app.current_user_id', ?, false)", [$memberA->id]);
        $conn->statement("SELECT set_config('app.current_role', 'member', false)");
        $rows = $conn->select('select id from borrowings');
        $this->assertCount(0, $rows);

        $conn->statement("SELECT set_config('app.current_user_id', ?, false)", [$memberB->id]);
        $rows = $conn->select('select id from borrowings');
        $this->assertCount(1, $rows);
        $this->assertSame($borrowing->id, (int) $rows[0]->id);
    }

    public function test_employee_history_shows_overdue_status(): void
    {
        $member = User::factory()->member()->create();
        $overdue = $this->makeBorrowing($member, [
            'due_at' => now()->subDays(2),
            'status' => 'overdue',
        ]);

        $employee = User::where('username', 'library')->first();
        $this->actingAs($employee)->get('/employee/borrowings/history')
            ->assertOk()
            ->assertSee($member->name)
            ->assertSee('overdue', false);
    }

    public function test_employee_history_and_member_list_render_soft_deleted_book(): void
    {
        $member = User::factory()->member()->create();
        $returned = $this->makeBorrowing($member, [
            'returned_at' => now(),
            'status' => 'returned',
        ]);
        $returned->book->delete();

        $employee = User::where('username', 'library')->first();
        $this->actingAs($employee)->get('/employee/borrowings/history')
            ->assertOk()
            ->assertSee($returned->book->title)
            ->assertSee('(deleted)');

        $this->actingAs($member)->get('/member/borrowings')
            ->assertOk()
            ->assertSee($returned->book->title)
            ->assertSee('(deleted)');
    }
}
