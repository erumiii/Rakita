<?php

namespace Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained();
            $table->foreignId('member_id')->constrained('users');
            // NULL while queued behind a borrowed book; set once the copy is
            // held for this member — they have PICKUP_DAYS to pick it up.
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        DB::statement("DO $$ BEGIN CREATE TYPE reservation_status AS ENUM ('pending', 'fulfilled', 'cancelled', 'expired'); EXCEPTION WHEN duplicate_object THEN NULL; END $$;");
        DB::statement("ALTER TABLE reservations ADD COLUMN status reservation_status NOT NULL DEFAULT 'pending'");

        // One active reservation per member per book.
        DB::statement("CREATE UNIQUE INDEX reservations_member_book_pending_unique ON reservations (member_id, book_id) WHERE status = 'pending'");

        // Grants for the runtime role, then RLS like borrowings/users.
        DB::statement('GRANT SELECT, INSERT, UPDATE, DELETE ON reservations TO library_app');

        DB::statement('ALTER TABLE reservations ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE reservations FORCE ROW LEVEL SECURITY');

        DB::statement("
            CREATE POLICY reservations_select ON reservations FOR SELECT
            USING (
                member_id = NULLIF(current_setting('app.current_user_id', true), '')::bigint
                OR current_setting('app.current_role', true) = 'employee'
            )");

        DB::statement("
            CREATE POLICY reservations_insert ON reservations FOR INSERT
            WITH CHECK (
                member_id = NULLIF(current_setting('app.current_user_id', true), '')::bigint
                OR current_setting('app.current_role', true) = 'employee'
            )");

        // Members may only flip their own reservation to 'cancelled'; everything
        // else (fulfill on borrow, offer promotion) runs as employee/superuser.
        DB::statement("
            CREATE POLICY reservations_update ON reservations FOR UPDATE
            USING (
                member_id = NULLIF(current_setting('app.current_user_id', true), '')::bigint
                OR current_setting('app.current_role', true) = 'employee'
            )
            WITH CHECK (
                current_setting('app.current_role', true) = 'employee'
                OR (member_id = NULLIF(current_setting('app.current_user_id', true), '')::bigint
                    AND status = 'cancelled')
            )");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE reservations NO FORCE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE reservations DISABLE ROW LEVEL SECURITY');

        foreach (['reservations_select', 'reservations_insert', 'reservations_update'] as $policy) {
            DB::statement("DROP POLICY IF EXISTS {$policy} ON reservations");
        }

        DB::statement('DROP INDEX IF EXISTS reservations_member_book_pending_unique');
        Schema::dropIfExists('reservations');
        DB::statement('DROP TYPE IF EXISTS reservation_status');
    }
};
