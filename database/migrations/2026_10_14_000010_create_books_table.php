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
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('author');
            $table->string('publisher');
            $table->unsignedInteger('page');
            $table->string('isbn')->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement("DO $$ BEGIN CREATE TYPE book_status AS ENUM ('isAvailable', 'isBorrowed'); EXCEPTION WHEN duplicate_object THEN NULL; END $$;");
        DB::statement("ALTER TABLE books ADD COLUMN status book_status NOT NULL DEFAULT 'isAvailable'");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE books DROP COLUMN IF EXISTS status');
        DB::statement('DROP TYPE IF EXISTS book_status');
        Schema::dropIfExists('books');
    }
};
