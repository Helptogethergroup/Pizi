<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Small key/value store for switches the admin changes from the admin panel.
        if (!Schema::hasTable('app_settings')) {
            Schema::create('app_settings', function (Blueprint $table) {
                $table->string('key', 80)->primary();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }

        // Every "please update the app" WhatsApp message that went out, so an owner gets one a day at most,
        // there is a cap, and the admin can see what was sent.
        Schema::create('owner_app_update_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('version_seen', 20)->nullable(); // the app version the owner had when the message was sent
            $table->string('latest_version', 20)->nullable();
            $table->timestamp('sent_at');
            $table->index(['user_id', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_app_update_reminders');
    }
};
