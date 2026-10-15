<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Public id carried inside the access token ("sid"). Never the refresh token.
            $table->char('uuid', 36)->unique();

            // Only hashes are stored, so a database leak cannot be replayed as a login.
            $table->char('refresh_hash', 64)->index();
            // The hash that was valid before the last rotation. Seeing it again
            // means a copied refresh token is being replayed.
            $table->char('previous_refresh_hash', 64)->nullable()->index();

            $table->string('device_id', 100)->nullable();
            $table->string('device_name', 150)->nullable();
            $table->string('platform', 20)->nullable();
            $table->string('app_version', 20)->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();

            $table->timestamp('last_used_at')->nullable();
            $table->dateTime('refresh_expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->string('revoked_reason', 40)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_sessions');
    }
};
