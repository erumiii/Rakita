<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_redirects_guest_to_login(): void
    {
        $this->get('/member/books')->assertRedirect('/login');
    }

    public function test_logs_employee_in_and_redirects_to_employee_portal(): void
    {
        $user = User::factory()->employee()->create();

        $this->post('/login', ['username' => $user->username, 'password' => 'password'])
            ->assertRedirect('/employee/books');

        $this->assertAuthenticatedAs($user);
    }

    public function test_logs_member_in_and_redirects_to_member_portal(): void
    {
        $user = User::factory()->member()->create();

        $this->post('/login', ['username' => $user->username, 'password' => 'password'])
            ->assertRedirect('/member/books');
    }

    public function test_rejects_wrong_password(): void
    {
        $user = User::factory()->create();

        $this->from('/login')
            ->post('/login', ['username' => $user->username, 'password' => 'wrong'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('username');
    }

    public function test_rate_limits_login_to_5_attempts_per_minute(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 5) as $i) {
            $this->post('/login', ['username' => $user->username, 'password' => 'wrong']);
        }

        $this->post('/login', ['username' => $user->username, 'password' => 'wrong'])
            ->assertStatus(429);
    }

    public function test_registers_member_with_hashed_password(): void
    {
        $this->post('/register', [
            'name' => 'Budi',
            'username' => 'Budi',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/member/books');

        $user = User::where('username', 'budi')->first();

        $this->assertNotNull($user);
        $this->assertSame('member', $user->role);
        $this->assertTrue(Hash::check('password', $user->password));
    }

    public function test_rejects_duplicate_username_case_insensitively(): void
    {
        User::factory()->create(['username' => 'budi']);

        $this->post('/register', [
            'name' => 'Other',
            'username' => 'BUDI',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('username');
    }

    public function test_rate_limits_register_to_6_attempts_per_minute(): void
    {
        // Invalid payloads keep the user guest — 'guest' middleware runs before
        // 'throttle' and would short-circuit authenticated requests with 302.
        foreach (range(1, 6) as $i) {
            $this->post('/register', [
                'name' => "U{$i}",
                'username' => "user{$i}",
                'password' => 'password',
            ]);
        }

        $this->post('/register', [
            'name' => 'U7',
            'username' => 'user7',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertStatus(429);
    }

    public function test_logout_redirects_to_login_and_guests_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect('/login');

        $this->assertGuest();
    }
}
