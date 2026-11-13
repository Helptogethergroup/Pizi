<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When the person chose their own password. An owner-added tenant starts with a random password
 * nobody knows (they sign in with an OTP); the tenant app lets them choose one later, once.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'password_set_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('password_set_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'password_set_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('password_set_at');
            });
        }
    }
};
