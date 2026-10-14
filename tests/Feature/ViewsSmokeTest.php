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
            ->assertSee('Masuk')
            ->assertSee('Belum punya akun?')
            ->assertSee('Daftar');

        $this->get('/register')
            ->assertOk()
            ->assertSee('Daftar')
            ->assertSee('Konfirmasi Password')
            ->assertSee('Sudah punya akun?');
    }

    public function test_employee_pages_render_with_indonesian_nav(): void
    {
        $employee = User::factory()->employee()->create();
        Book::factory()->create(['title' => 'Smoke Target', 'status' => 'isAvailable']);

        $this->actingAs($employee)->get('/employee/books')
            ->assertOk()
            ->assertSee('Buku')
            ->assertSee('Peminjaman')
            ->assertSee('Pengembalian')
            ->assertSee('Riwayat')
            ->assertSee('Smoke Target');

        $this->actingAs($employee)->get('/employee/borrowings/create')
            ->assertOk()
            ->assertSee('Pilih member')
            ->assertSee('Pilih buku')
            ->assertSee('Periode (hari)');

        $this->actingAs($employee)->get('/employee/returns/create')
            ->assertOk()
            ->assertSee('Belum ada data.');

        $this->actingAs($employee)->get('/employee/borrowings/history')
            ->assertOk()
            ->assertSee('Peminjam')
            ->assertSee('Belum ada data.');

        $this->actingAs($employee)->get('/employee/members')
            ->assertOk()
            ->assertSee('Pinjaman aktif');
    }

    public function test_member_pages_render_with_status_filter(): void
    {
        $member = User::factory()->member()->create();
        Book::factory()->create(['title' => 'Available Book', 'status' => 'isAvailable']);
        Book::factory()->create(['title' => 'Borrowed Book', 'status' => 'isBorrowed']);

        $this->actingAs($member)->get('/member/books')
            ->assertOk()
            ->assertSee('Koleksi Buku')
            ->assertSee('Pinjaman Saya')
            ->assertSee('Semua')
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
            ->assertSee('Sedang Dipinjam')
            ->assertSee('Sudah Dikembalikan')
            ->assertSee('Belum ada data.');
    }
}
