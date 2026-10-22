<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An owner can tell us that a lead they unlocked was not real. The Pizi team
 * checks it and, if the owner is right, the credits go back.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lead_reports')) {
            return;
        }

        Schema::create('lead_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id')->index();
            // the owner the lead belongs to (a manager's report counts for their owner)
            $table->unsignedBigInteger('owner_id')->index();
            // who actually reported (the owner or one of their managers)
            $table->unsignedBigInteger('reported_by')->index();
            // who paid for the unlock: the refund goes back to this person's wallet
            $table->unsignedBigInteger('paid_by')->nullable();
            $table->unsignedInteger('credits_spent')->default(0);
            $table->string('reason', 30);
            $table->text('note')->nullable();
            $table->string('status', 12)->default('pending')->index(); // pending | approved | rejected
            $table->text('admin_note')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedInteger('credits_refunded')->default(0);
            $table->timestamps();

            // one report per lead per owner
            $table->unique(['lead_id', 'owner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_reports');
    }
};
