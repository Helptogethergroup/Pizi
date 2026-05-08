<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','owner','telecaller','field_executive','seo_manager','guest') NOT NULL DEFAULT 'guest'");
            return;
        }

        if ($driver === 'sqlite') {
            // SQLite doesn't support ALTER COLUMN — recreate the table.
            // Step 1: Create new table with updated CHECK constraint
            Schema::create('users_new', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('phone', 20)->nullable();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->enum('role', ['admin','owner','telecaller','field_executive','seo_manager','guest'])->default('guest');
                $table->boolean('is_active')->default(true);
                $table->rememberToken();
                $table->timestamps();
            });

            // Step 2: Copy data
            DB::statement('INSERT INTO users_new (id, name, email, phone, email_verified_at, password, role, is_active, remember_token, created_at, updated_at)
                           SELECT id, name, email, phone, email_verified_at, password, role, is_active, remember_token, created_at, updated_at FROM users');

            // Step 3: Drop old, rename new
            Schema::drop('users');
            Schema::rename('users_new', 'users');
        }
    }

    public function down(): void
    {
        // No rollback — data preserved
    }
};