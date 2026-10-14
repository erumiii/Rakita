<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeBooksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_creates_book_with_default_available_status(): void
    {
        $employee = User::factory()->employee()->create();
        $data = ['title' => 'T', 'author' => 'A', 'publisher' => 'P', 'page' => 100, 'isbn' => '978-1'];

        $this->actingAs($employee)->post('/employee/books', $data)->assertRedirect('/employee/books');

        $book = Book::where('isbn', '978-1')->first();
        $this->assertNotNull($book);
        $this->assertSame('isAvailable', $book->status);
    }

    public function test_rejects_duplicate_isbn(): void
    {
        $employee = User::factory()->employee()->create();
        Book::factory()->create(['isbn' => '978-1']);
        $data = ['title' => 'T', 'author' => 'A', 'publisher' => 'P', 'page' => 100, 'isbn' => '978-1'];

        $this->actingAs($employee)->post('/employee/books', $data)->assertSessionHasErrors('isbn');
    }

    public function test_validates_page_positive_integer(): void
    {
        $employee = User::factory()->employee()->create();
        $data = ['title' => 'T', 'author' => 'A', 'publisher' => 'P', 'page' => 0, 'isbn' => '978-2'];

        $this->actingAs($employee)->post('/employee/books', $data)->assertSessionHasErrors('page');
    }

    public function test_updates_book(): void
    {
        $employee = User::factory()->employee()->create();
        $book = Book::factory()->create();
        $this->actingAs($employee)->put("/employee/books/{$book->id}", [
            'title' => 'New', 'author' => $book->author, 'publisher' => $book->publisher,
            'page' => $book->page, 'isbn' => $book->isbn,
        ])->assertRedirect('/employee/books');

        $this->assertSame('New', $book->refresh()->title);
    }

    public function test_soft_deletes_available_book(): void
    {
        $employee = User::factory()->employee()->create();
        $book = Book::factory()->create();
        $this->actingAs($employee)->delete("/employee/books/{$book->id}")->assertRedirect('/employee/books');

        $this->assertNotNull($book->refresh()->deleted_at);
    }

    public function test_searches_and_filters(): void
    {
        $employee = User::factory()->employee()->create();
        Book::factory()->create(['title' => 'Laravel Deep Dive', 'status' => 'isAvailable']);
        Book::factory()->create(['title' => 'PHP Basics', 'status' => 'isBorrowed']);

        $this->actingAs($employee)->get('/employee/books?q=laravel')
            ->assertOk()->assertSee('Laravel Deep Dive')->assertDontSee('PHP Basics');

        $this->actingAs($employee)->get('/employee/books?status=isBorrowed')
            ->assertOk()->assertSee('PHP Basics')->assertDontSee('Laravel Deep Dive');
    }
}
