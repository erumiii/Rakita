<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RlsBootTest extends TestCase
{
    use RefreshDatabase;

    public function test_hides_all_rows_from_library_app_when_no_session_vars_set(): void
    {
        User::factory()->count(3)->create();

        $this->assertSame(
            0,
            (int) DB::connection('pgsql_app')->select('select count(*) as c from users')[0]->c
        );
        $this->assertSame(
            3,
            (int) DB::select('select count(*) as c from users')[0]->c
        );
    }
}
