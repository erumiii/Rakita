<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_blocks_member_from_employee_portal(): void
    {
        $member = User::factory()->member()->create();

        $this->actingAs($member)->get('/employee/books')->assertForbidden();
    }

    public function test_blocks_employee_from_member_portal(): void
    {
        $employee = User::factory()->employee()->create();

        $this->actingAs($employee)->get('/member/books')->assertForbidden();
    }

    public function test_allows_employee_books_list(): void
    {
        $employee = User::factory()->employee()->create();

        $this->actingAs($employee)->get('/employee/books')->assertOk();
    }
}
