<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class DbSession
{
    public static function apply(User $user): void
    {
        // Same connection the models use; session-scoped GUCs only live there.
        DB::connection('pgsql_app')->statement("SELECT set_config('app.current_user_id', ?, false)", [$user->id]);
        DB::connection('pgsql_app')->statement("SELECT set_config('app.current_role', ?, false)", [$user->role]);
    }
}
