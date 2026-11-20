<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tenant_app_invites')) {
            return;
        }

        // One row per phone number that was sent the "download the Pizi tenant app" WhatsApp, so the same person
        // is never messaged twice (even when different owners add them, or they are added again after moving out).
        // A number WhatsApp refuses is tried at most 3 times, then left alone.
        Schema::create('tenant_app_invites', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 10)->unique(); // last 10 digits
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->string('status', 10)->default('sent'); // sent | failed
            $table->unsignedTinyInteger('attempts')->default(1);
            $table->timestamp('sent_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_app_invites');
    }
};
