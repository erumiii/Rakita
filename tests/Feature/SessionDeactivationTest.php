<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SessionDeactivationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_deactivated_member_is_logged_out_on_next_request(): void
    {
        $member = User::factory()->member()->create();

        // Real login: actingAs would re-authenticate on every request and mask
        // the session invalidation the middleware is supposed to enforce.
        $this->post('/login', ['username' => $member->username, 'password' => 'password'])
            ->assertRedirect('/member/books');

        // Employee flips is_active via superuser connection (outside RLS).
        DB::table('users')->where('id', $member->id)->update(['is_active' => false]);

        // New HTTP request = fresh guard; the in-test guard caches the user
        // object from the login request, so clear it to mimic a real boundary.
        $this->app['auth']->forgetGuards();

        $this->get('/member/books')->assertRedirect('/login');
        $this->assertGuest();
    }
}
