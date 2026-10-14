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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('username');
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        DB::statement('CREATE UNIQUE INDEX users_username_lower_unique_idx ON users (LOWER(username))');

        DB::statement("DO $$ BEGIN CREATE TYPE user_role AS ENUM ('employee', 'member'); EXCEPTION WHEN duplicate_object THEN NULL; END $$;");
        DB::statement("ALTER TABLE users ADD COLUMN role user_role NOT NULL DEFAULT 'member'");

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');

        DB::statement('ALTER TABLE users DROP COLUMN IF EXISTS role');
        DB::statement('DROP TYPE IF EXISTS user_role');
        DB::statement('DROP INDEX IF EXISTS users_username_lower_unique_idx');

        Schema::dropIfExists('users');
    }
};
