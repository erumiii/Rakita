<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NotificationsRlsTest extends TestCase
{
    // pgsql_app can't see the default connection's transaction — fresh per test.
    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate:fresh');
        $this->seed(DatabaseSeeder::class);
    }

    private function insertSharedEmployeeRow(): void
    {
        DB::connection('pgsql')->table('notifications')->insert([
            'user_id' => null, 'recipient_role' => 'employee', 'type' => 'member.registered',
            'payload' => '{"link":"/employee/members"}', 'created_at' => now(),
        ]);
    }

    public function test_member_sees_only_own_rows(): void
    {
        $member = User::factory()->member()->create();
        $other = User::factory()->member()->create();

        DB::connection('pgsql')->table('notifications')->insert([
            ['user_id' => $member->id, 'recipient_role' => null, 'type' => 'borrowing.due_soon', 'payload' => '{"link":"/member/borrowings"}', 'created_at' => now()],
            ['user_id' => $other->id, 'recipient_role' => null, 'type' => 'borrowing.due_soon', 'payload' => '{"link":"/member/borrowings"}', 'created_at' => now()],
        ]);
        $this->insertSharedEmployeeRow();

        $this->actingAs($member)->get('/member/books');
        $rows = DB::connection('pgsql_app')->table('notifications')->get();
        $this->assertCount(1, $rows);
        $this->assertSame('borrowing.due_soon', $rows[0]->type);
    }

    public function test_employee_sees_only_shared_rows(): void
    {
        $member = User::factory()->member()->create();

        DB::connection('pgsql')->table('notifications')->insert([
            ['user_id' => $member->id, 'recipient_role' => null, 'type' => 'borrowing.due_soon', 'payload' => '{"link":"/member/borrowings"}', 'created_at' => now()],
        ]);
        $this->insertSharedEmployeeRow();

        $employee = User::factory()->employee()->create();
        $this->actingAs($employee)->get('/employee/books');
        $rows = DB::connection('pgsql_app')->table('notifications')->get();
        $this->assertCount(1, $rows);
        $this->assertSame('member.registered', $rows[0]->type);
    }

    public function test_member_cannot_insert_private_row_for_someone_else(): void
    {
        $member = User::factory()->member()->create();
        $this->actingAs($member)->get('/member/books');

        $this->expectException(QueryException::class);
        DB::connection('pgsql_app')->table('notifications')->insert([
            'user_id' => null, 'recipient_role' => null, 'type' => 'x', 'payload' => '{}', 'created_at' => now(),
        ]);
    }

    public function test_member_can_insert_employee_targeted_row(): void
    {
        $member = User::factory()->member()->create();
        $this->actingAs($member)->get('/member/books');

        DB::connection('pgsql_app')->table('notifications')->insert([
            'user_id' => null, 'recipient_role' => 'employee', 'type' => 'reservation.created',
            'payload' => '{"link":"/employee/reservations"}', 'created_at' => now(),
        ]);

        $count = DB::connection('pgsql')->table('notifications')->where('type', 'reservation.created')->count();
        $this->assertSame(1, $count);
    }
}
