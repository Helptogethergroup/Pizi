<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bed Plan: an owner pays a monthly rate for every bed they keep on Pizi, from a Bed Wallet that is
 * separate from the Lead Wallet (lead credits). The admin decides every price. All amounts are in rupees
 * and the GST is already inside the price.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Price lists the admin makes. Price is per bed per month, GST included.
        Schema::create('bed_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price_per_bed', 10, 2);
            $table->decimal('gst_percent', 5, 2)->default(18);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // One row per owner the plan has been switched on for. No row = the owner is not on the plan
        // (nothing is charged and nothing is restricted).
        Schema::create('bed_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('bed_plan_id')->nullable()->constrained('bed_plans')->nullOnDelete();
            $table->decimal('custom_price_per_bed', 10, 2)->nullable(); // admin override for this owner
            // active: paid up to cycle_end | expired: not renewed, owner is restricted | disabled: admin switched it off
            $table->string('status', 20)->default('active')->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('cycle_start')->nullable();
            $table->timestamp('cycle_end')->nullable()->index();
            $table->unsignedInteger('paid_beds')->default(0); // beds already paid for in this cycle
            $table->timestamp('last_renewed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('bed_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('balance', 12, 2)->default(0);
            $table->decimal('lifetime_added', 12, 2)->default(0);
            $table->decimal('lifetime_spent', 12, 2)->default(0);
            $table->timestamps();
        });

        // Every rupee that moves. idempotency_key makes sure the same charge or top-up can never be booked twice.
        Schema::create('bed_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('bed_wallets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 10); // credit | debit
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->string('source', 30); // topup, admin_credit, admin_debit, plan_renewal, new_beds, refund
            $table->unsignedInteger('beds')->nullable();
            $table->decimal('gst_amount', 12, 2)->nullable(); // the GST inside a plan charge
            $table->string('reference')->nullable();
            $table->string('idempotency_key', 120)->nullable()->unique();
            $table->text('notes')->nullable();
            $table->foreignId('actioned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });

        // Top-up packages the admin makes: pay amount_inr, wallet gets amount_inr + bonus_inr.
        Schema::create('bed_topup_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('amount_inr');
            $table->unsignedInteger('bonus_inr')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('bed_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bed_topup_package_id')->nullable()->constrained('bed_topup_packages')->nullOnDelete();
            $table->unsignedInteger('amount_inr');
            $table->unsignedInteger('wallet_credit');
            $table->string('razorpay_order_id')->unique();
            $table->string('razorpay_payment_id')->nullable();
            $table->string('status', 20)->default('created')->index(); // created | paid | failed
            $table->text('failure_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('bed_plan_settings', function (Blueprint $table) {
            $table->string('key', 60)->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bed_plan_settings');
        Schema::dropIfExists('bed_payments');
        Schema::dropIfExists('bed_topup_packages');
        Schema::dropIfExists('bed_wallet_transactions');
        Schema::dropIfExists('bed_wallets');
        Schema::dropIfExists('bed_subscriptions');
        Schema::dropIfExists('bed_plans');
    }
};
