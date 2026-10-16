<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NotificationsUiTest extends TestCase
{
    // pgsql_app can't see the default connection's transaction — fresh per test.
    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate:fresh');
        $this->seed(DatabaseSeeder::class);
    }

    private function insertNotification(int $userId, string $payload, ?string $createdAt = null): void
    {
        DB::connection('pgsql')->table('notifications')->insert([
            'user_id' => $userId, 'recipient_role' => null, 'type' => 'book.added',
            'payload' => $payload, 'created_at' => $createdAt ?? now(),
        ]);
    }

    public function test_bell_badge_shows_unread_count(): void
    {
        $member = User::factory()->member()->create();
        $this->insertNotification($member->id, '{"title":"Clean Code","link":"/member/books"}');
        $this->insertNotification($member->id, '{"title":"Refactoring","link":"/member/books"}');

        $this->actingAs($member)->get('/member/books')
            ->assertSee('data-notification-count', false)
            ->assertSeeInOrder(['data-notification-count', '2'], false);
    }

    public function test_dropdown_lists_only_rls_visible_rows_newest_first(): void
    {
        $member = User::factory()->member()->create();
        $other = User::factory()->member()->create();
        $this->insertNotification($other->id, '{"title":"Secret","link":"/member/books"}');
        $this->insertNotification($member->id, '{"title":"Clean Code","link":"/member/books"}', now()->addMinute());

        $this->actingAs($member)->get('/member/books')
            ->assertSee('Clean Code')
            ->assertDontSee('Secret');
    }

    public function test_mark_read_marks_only_own_row(): void
    {
        $member = User::factory()->member()->create();
        $other = User::factory()->member()->create();
        $own = DB::connection('pgsql')->table('notifications')->insertGetId([
            'user_id' => $member->id, 'recipient_role' => null, 'type' => 'book.added', 'payload' => '{"link":"/member/books"}', 'created_at' => now(),
        ]);
        DB::connection('pgsql')->table('notifications')->insert([
            'user_id' => $other->id, 'recipient_role' => null, 'type' => 'book.added', 'payload' => '{"link":"/member/books"}', 'created_at' => now(),
        ]);

        $this->actingAs($member)->post("/notifications/{$own}/read")->assertRedirect('/member/books');

        $this->assertNotNull(DB::connection('pgsql')->table('notifications')->where('id', $own)->value('read_at'));
    }

    public function test_mark_read_other_members_row_404s(): void
    {
        $member = User::factory()->member()->create();
        $other = User::factory()->member()->create();
        $foreign = DB::connection('pgsql')->table('notifications')->insertGetId([
            'user_id' => $other->id, 'recipient_role' => null, 'type' => 'book.added', 'payload' => '{"link":"/member/books"}', 'created_at' => now(),
        ]);

        $this->actingAs($member)->post("/notifications/{$foreign}/read")->assertNotFound();
    }

    public function test_mark_all_read(): void
    {
        $member = User::factory()->member()->create();
        $this->insertNotification($member->id, '{"link":"/member/books"}');
        $this->insertNotification($member->id, '{"link":"/member/books"}');

        $this->actingAs($member)->post('/notifications/read-all')->assertRedirect();

        $unread = DB::connection('pgsql')->table('notifications')->whereNull('read_at')->count();
        $this->assertSame(0, $unread);
    }
}
