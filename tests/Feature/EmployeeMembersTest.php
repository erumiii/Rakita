<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmployeeMembersTest extends TestCase
{
    // ponytail: no RefreshDatabase — pgsql_app (library_app) can't see rows
    // inside the default connection's transaction; migrate:fresh per test
    // (same pattern as BorrowReturnTest/RlsBootTest).
    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate:fresh');
        $this->seed(DatabaseSeeder::class);
    }

    public function test_employee_sees_member_list(): void
    {
        $employee = User::factory()->employee()->create();
        $member = User::factory()->member()->create();

        $this->actingAs($employee)->get('/employee/members')
            ->assertOk()
            ->assertSee($member->name);
    }

    public function test_deactivates_member_and_blocks_login(): void
    {
        $employee = User::factory()->employee()->create();
        $member = User::factory()->member()->create();

        $this->actingAs($employee)->put("/employee/members/{$member->id}", [
            'name' => $member->name,
            'password' => '',
            'is_active' => '0',
        ])->assertRedirect('/employee/members');

        // actingAs persists across requests; clear session so guest login runs.
        $this->post('/logout');

        $this->from('/login')
            ->post('/login', ['username' => $member->username, 'password' => 'password'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('username');
    }

    public function test_updates_member_name(): void
    {
        $employee = User::factory()->employee()->create();
        $member = User::factory()->member()->create();

        $this->actingAs($employee)->put("/employee/members/{$member->id}", [
            'name' => 'New Name',
            'password' => '',
            'is_active' => '1',
        ])->assertRedirect('/employee/members');

        $this->assertSame('New Name', $member->refresh()->name);
    }

    public function test_updates_password_hashed(): void
    {
        $employee = User::factory()->employee()->create();
        $member = User::factory()->member()->create(['password' => Hash::make('password')]);
        $oldHash = $member->password;

        $this->actingAs($employee)->put("/employee/members/{$member->id}", [
            'name' => $member->name,
            'password' => 'newpassword',
            'is_active' => '1',
        ])->assertRedirect('/employee/members');

        $member->refresh();
        $this->assertNotSame($oldHash, $member->password);
        $this->assertTrue(Hash::check('newpassword', $member->password));
    }

    public function test_member_cannot_access_member_edit_routes(): void
    {
        $member = User::factory()->member()->create();

        $this->actingAs($member)->get("/employee/members/{$member->id}/edit")->assertForbidden();
        $this->actingAs($member)->put("/employee/members/{$member->id}", [
            'name' => 'x', 'password' => '', 'is_active' => '1',
        ])->assertForbidden();
    }
}
