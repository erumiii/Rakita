<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pgsql')->create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('recipient_role')->nullable();
            $table->text('type');
            $table->jsonb('payload')->default('{}');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        DB::statement('ALTER TABLE notifications ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE notifications FORCE ROW LEVEL SECURITY');

        DB::statement("
            CREATE POLICY notifications_select ON notifications FOR SELECT
            USING (
                user_id = NULLIF(current_setting('app.current_user_id', true), '')::bigint
                OR (user_id IS NULL AND recipient_role = current_setting('app.current_role', true))
            )");
    }

    public function down(): void
    {
        DB::statement('DROP POLICY IF EXISTS notifications_select ON notifications');
        DB::statement('ALTER TABLE notifications NO FORCE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE notifications DISABLE ROW LEVEL SECURITY');
        Schema::connection('pgsql')->dropIfExists('notifications');
    }
};
