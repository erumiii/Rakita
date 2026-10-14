<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\DbSession;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RlsBootTest extends TestCase
{
    // ponytail: no RefreshDatabase — borrowings/users rows must be committed so
    // the pgsql_app connection (library_app) can see them; migrate:fresh per test.
    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate:fresh');
        $this->seed(DatabaseSeeder::class);
    }

    public function test_hides_all_rows_from_library_app_when_no_session_vars_set(): void
    {
        User::factory()->count(3)->create();

        $this->assertSame(
            0,
            (int) DB::connection('pgsql_app')->select('select count(*) as c from users')[0]->c
        );
        $this->assertSame(
            4,
            (int) DB::select('select count(*) as c from users')[0]->c
        );
    }

    public function test_runtime_connection_is_library_app_and_member_context_restricts_borrowings(): void
    {
        $this->assertSame('library_app', DB::connection('pgsql_app')->select('select current_user')[0]->current_user);

        $employee = User::where('username', 'library')->first();
        $memberA = User::factory()->member()->create();
        $memberB = User::factory()->member()->create();
        $books = DB::select('select id from books order by id limit 2');

        foreach ([[$memberA, $books[0]->id], [$memberB, $books[1]->id]] as [$member, $bookId]) {
            DB::table('borrowings')->insert([
                'book_id' => $bookId,
                'member_id' => $member->id,
                'processed_by' => $employee->id,
                'borrowed_at' => now(),
                'borrow_period_days' => 7,
                'due_at' => now()->addDays(7),
                'status' => 'borrowed',
            ]);
        }

        DbSession::apply($memberA);
        $this->assertSame(
            1,
            (int) DB::connection('pgsql_app')->select('select count(*) as c from borrowings')[0]->c
        );

        DbSession::apply($employee);
        $this->assertSame(
            2,
            (int) DB::connection('pgsql_app')->select('select count(*) as c from borrowings')[0]->c
        );
    }

    public function test_member_context_cannot_escalate_own_role_via_update(): void
    {
        $member = User::factory()->member()->create();
        DbSession::apply($member);

        // Same-role update (own profile) still allowed.
        $affected = DB::connection('pgsql_app')->update(
            'update users set name = ? where id = ?', ['Hacked Name', $member->id]
        );
        $this->assertSame(1, $affected);

        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::connection('pgsql_app')->update(
            'update users set role = ? where id = ?', ['employee', $member->id]
        );
    }

    public function test_employee_context_still_updates_any_user_row(): void
    {
        $member = User::factory()->member()->create();
        $employee = User::where('username', 'library')->first();
        DbSession::apply($employee);

        $affected = DB::connection('pgsql_app')->update(
            'update users set role = ? where id = ?', ['member', $member->id]
        );
        $this->assertSame(1, $affected);
    }
}
