<?php

namespace Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Grants for the runtime role (books has no RLS: shared catalog).
        DB::statement('GRANT USAGE ON SCHEMA public TO library_app');
        DB::statement('GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO library_app');
        DB::statement('GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO library_app');
        DB::statement('ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES TO library_app');
        DB::statement('ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT USAGE, SELECT ON SEQUENCES TO library_app');

        foreach (['borrowings', 'users'] as $t) {
            DB::statement("ALTER TABLE {$t} ENABLE ROW LEVEL SECURITY");
            DB::statement("ALTER TABLE {$t} FORCE ROW LEVEL SECURITY");
        }

        DB::statement("
            CREATE POLICY borrowings_select ON borrowings FOR SELECT
            USING (
                member_id = NULLIF(current_setting('app.current_user_id', true), '')::bigint
                OR current_setting('app.current_role', true) = 'employee'
            )");

        DB::statement("
            CREATE POLICY borrowings_employee_insert ON borrowings FOR INSERT
            WITH CHECK (current_setting('app.current_role', true) = 'employee')");

        DB::statement("
            CREATE POLICY borrowings_employee_update ON borrowings FOR UPDATE
            USING (current_setting('app.current_role', true) = 'employee')
            WITH CHECK (true)");

        DB::statement("
            CREATE POLICY borrowings_employee_delete ON borrowings FOR DELETE
            USING (current_setting('app.current_role', true) = 'employee')");

        // users: member sees own row; employee sees all; member can update own row (profile)
        DB::statement("
            CREATE POLICY users_select ON users FOR SELECT
            USING (
                id = NULLIF(current_setting('app.current_user_id', true), '')::bigint
                OR current_setting('app.current_role', true) = 'employee'
            )");

        DB::statement("
            CREATE POLICY users_insert ON users FOR INSERT
            WITH CHECK (current_setting('app.current_role', true) = 'employee' OR role = 'member')");

        // member updates own row only (employee updates any)
        DB::statement("
            CREATE POLICY users_update ON users FOR UPDATE
            USING (
                id = NULLIF(current_setting('app.current_user_id', true), '')::bigint
                OR current_setting('app.current_role', true) = 'employee'
            )
            WITH CHECK (
                -- member's new row must stay role='member' (no self-promotion);
                -- employee branch is unconstrained
                (id = NULLIF(current_setting('app.current_user_id', true), '')::bigint
                    AND role = 'member')
                OR current_setting('app.current_role', true) = 'employee'
            )");
    }

    public function down(): void
    {
        foreach (['users', 'borrowings'] as $t) {
            DB::statement("ALTER TABLE {$t} NO FORCE ROW LEVEL SECURITY");
            DB::statement("ALTER TABLE {$t} DISABLE ROW LEVEL SECURITY");
        }

        $policies = [
            'borrowings_select' => 'borrowings',
            'borrowings_employee_insert' => 'borrowings',
            'borrowings_employee_update' => 'borrowings',
            'borrowings_employee_delete' => 'borrowings',
            'users_select' => 'users',
            'users_insert' => 'users',
            'users_update' => 'users',
        ];

        foreach ($policies as $policy => $table) {
            DB::statement("DROP POLICY IF EXISTS {$policy} ON {$table}");
        }
    }
};
