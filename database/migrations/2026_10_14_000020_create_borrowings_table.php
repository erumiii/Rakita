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
        Schema::create('borrowings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained();
            $table->foreignId('member_id')->constrained('users');
            $table->foreignId('processed_by')->constrained('users');
            $table->timestamp('borrowed_at');
            $table->unsignedTinyInteger('borrow_period_days');
            $table->timestamp('due_at');
            $table->timestamp('returned_at')->nullable();
            $table->timestamps();
        });

        DB::statement("DO $$ BEGIN CREATE TYPE borrowing_status AS ENUM ('borrowed', 'returned', 'overdue'); EXCEPTION WHEN duplicate_object THEN NULL; END $$;");
        DB::statement("ALTER TABLE borrowings ADD COLUMN status borrowing_status NOT NULL DEFAULT 'borrowed'");
        DB::statement('CREATE UNIQUE INDEX borrowings_book_active_unique ON borrowings (book_id) WHERE returned_at IS NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS borrowings_book_active_unique');
        DB::statement('ALTER TABLE borrowings DROP COLUMN IF EXISTS status');
        DB::statement('DROP TYPE IF EXISTS borrowing_status');
        Schema::dropIfExists('borrowings');
    }
};
