<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

// ponytail: string-assert smoke of polished views, not full AC replay —
// deeper flows already covered by EmployeeBooksTest/BorrowReturnTest/MemberPortalTest.
class ViewsSmokeTest extends TestCase
{
    // ponytail: no RefreshDatabase — pgsql_app (library_app) can't see rows
    // inside the default connection's transaction; migrate:fresh per test
    // (same pattern as BorrowReturnTest/MemberPortalTest).
    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate:fresh');
        $this->seed(DatabaseSeeder::class);
    }

    public function test_auth_pages_render_polished_cards(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Sign In')
            ->assertSee("Don't have an account?", false)
            ->assertSee('Create an account');

        $this->get('/register')
            ->assertOk()
            ->assertSee('Register')
            ->assertSee('Confirm Password')
            ->assertSee('Already have an account?');
    }

    public function test_employee_pages_render_with_english_nav(): void
    {
        $employee = User::factory()->employee()->create();
        Book::factory()->create(['title' => 'Smoke Target', 'status' => 'isAvailable']);

        $this->actingAs($employee)->get('/employee/books')
            ->assertOk()
            ->assertSee('Books')
            ->assertSee('Borrow')
            ->assertSee('Members')
            ->assertSee('Smoke Target');

        $this->actingAs($employee)->get('/employee/borrowings/history')
            ->assertOk()
            ->assertSee('Borrower')
            ->assertSee('No data yet.')
            ->assertSee('+ New Borrowing');

        $this->actingAs($employee)->get('/employee/members')
            ->assertOk()
            ->assertSee('Active Borrowings');
    }

    public function test_notification_bell_renders_on_employee_and_member_pages(): void
    {
        $employee = User::factory()->employee()->create();
        $member = User::factory()->member()->create();

        $this->actingAs($employee)->get('/employee/books')
            ->assertOk()
            ->assertSee('data-notification-toggle', false);

        $this->actingAs($member)->get('/member/books')
            ->assertOk()
            ->assertSee('data-notification-toggle', false);
    }

    public function test_member_pages_render_with_status_filter(): void
    {
        $member = User::factory()->member()->create();
        Book::factory()->create(['title' => 'Available Book', 'status' => 'isAvailable']);
        Book::factory()->create(['title' => 'Borrowed Book', 'status' => 'isBorrowed']);

        $this->actingAs($member)->get('/member/books')
            ->assertOk()
            ->assertSee('Book Collection')
            ->assertSee('My Borrowings')
            ->assertSee('All')
            ->assertSee('Available Book')
            ->assertSee('Borrowed Book');

        $this->actingAs($member)->get('/member/books?status=isAvailable')
            ->assertOk()
            ->assertSee('Available Book')
            ->assertDontSee('Borrowed Book');

        $this->actingAs($member)->get('/member/books?status=isBorrowed')
            ->assertOk()
            ->assertSee('Borrowed Book')
            ->assertDontSee('Available Book');

        $this->actingAs($member)->get('/member/borrowings')
            ->assertOk()
            ->assertSee('Currently Borrowed')
            ->assertSee('Returned')
            ->assertSee('No data yet.');
    }
}
