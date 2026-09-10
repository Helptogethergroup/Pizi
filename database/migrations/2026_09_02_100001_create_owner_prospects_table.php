<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('owner_prospects')) {
            return;
        }

        Schema::create('owner_prospects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('telecaller_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('phone', 20);
            $table->string('source', 50)->nullable(); // cold_call, referral, market_visit, other
            $table->text('notes')->nullable();

            // Call tracking (same shape as tenant leads, kept separate on
            // purpose — this is B2B owner outreach, not a tenant enquiry)
            $table->string('call_status', 30)->nullable(); // attended, no_answer, not_interested
            $table->timestamp('called_at')->nullable();
            $table->unsignedTinyInteger('call_attempts')->default(0);
            $table->string('rejection_reason', 50)->nullable();

            // Conversion pipeline — telecaller marks these manually once the
            // owner has actually done them (no auto-detection).
            $table->timestamp('registered_at')->nullable();
            $table->timestamp('property_listed_at')->nullable();
            $table->timestamp('paid_plan_at')->nullable();

            // Optional link once the owner's real account exists on Pizi —
            // lets the telecaller jump straight to that owner's record.
            $table->foreignId('linked_owner_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['telecaller_id', 'call_status']);
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_prospects');
    }
};
