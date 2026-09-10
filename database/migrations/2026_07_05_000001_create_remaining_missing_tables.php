<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Creates all remaining tables referenced in app code but missing from DB:
 *  1. user_activity_log     — login/logout audit trail (BLOCKS LOGIN without this)
 *  2. amenity_property      — API variant of property_amenities pivot
 *  3. credit_wallets        — API variant of wallets (owner credit balance)
 *  4. credit_transactions   — API variant of wallet_transactions
 *  5. lead_pricing_configs  — API variant of lead_pricing
 *  6. pricing_settings      — key-value settings table (lead_unlock_cost etc.)
 *  7. lead_remarks          — per-lead call/notes log
 *  8. visit_media           — media files attached to field visits (API version)
 *  9. payment_orders        — Razorpay order tracking for credit purchases
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. user_activity_log ─────────────────────────────────────────────
        if (!Schema::hasTable('user_activity_log')) {
            Schema::create('user_activity_log', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('action', 60);          // login, logout, password_change, etc.
                $table->string('description')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['user_id', 'action']);
                $table->index('created_at');
            });
        }

        // ── 2. amenity_property (API pivot — mirrors property_amenities) ─────
        if (!Schema::hasTable('amenity_property')) {
            Schema::create('amenity_property', function (Blueprint $table) {
                $table->foreignId('property_id')->constrained()->cascadeOnDelete();
                $table->foreignId('amenity_id')->constrained()->cascadeOnDelete();
                $table->primary(['property_id', 'amenity_id']);
            });
        }

        // ── 3. credit_wallets (API wallet — mirrors wallets) ─────────────────
        if (!Schema::hasTable('credit_wallets')) {
            Schema::create('credit_wallets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('owner_id')->unique()->constrained('users')->cascadeOnDelete();
                $table->integer('balance')->default(0);
                $table->integer('lifetime_added')->default(0);
                $table->integer('lifetime_spent')->default(0);
                $table->timestamps();
            });
        }

        // ── 4. credit_transactions (API txn log — mirrors wallet_transactions)
        if (!Schema::hasTable('credit_transactions')) {
            Schema::create('credit_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
                $table->enum('type', ['credit', 'debit', 'purchase', 'refund', 'adjustment']);
                $table->integer('amount');
                $table->integer('balance_after')->default(0);
                $table->string('source', 60)->nullable();  // admin_credit, purchase, lead_unlock
                $table->string('reference')->nullable();   // razorpay order id, etc.
                $table->text('notes')->nullable();
                $table->foreignId('actioned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['owner_id', 'created_at']);
            });
        }

        // ── 5. lead_pricing_configs (API pricing config) ─────────────────────
        if (!Schema::hasTable('lead_pricing_configs')) {
            Schema::create('lead_pricing_configs', function (Blueprint $table) {
                $table->id();
                $table->string('lead_type', 50);    // direct, verified, converted, manual
                $table->integer('credit_cost')->default(1);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            // Seed default pricing
            DB::table('lead_pricing_configs')->insert([
                ['lead_type' => 'direct',    'credit_cost' => 1,  'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
                ['lead_type' => 'verified',  'credit_cost' => 2,  'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
                ['lead_type' => 'converted', 'credit_cost' => 3,  'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
                ['lead_type' => 'manual',    'credit_cost' => 1,  'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        // ── 6. pricing_settings (key-value config store) ─────────────────────
        if (!Schema::hasTable('pricing_settings')) {
            Schema::create('pricing_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->string('value');
                $table->string('label')->nullable();
                $table->timestamps();
            });

            // Seed default settings
            DB::table('pricing_settings')->insert([
                ['key' => 'lead_unlock_cost',  'value' => '1',  'label' => 'Credits per lead unlock', 'created_at' => now(), 'updated_at' => now()],
                ['key' => 'min_wallet_balance','value' => '5',  'label' => 'Low balance warning threshold', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        // ── 7. lead_remarks (per-lead call notes / remarks) ──────────────────
        if (!Schema::hasTable('lead_remarks')) {
            Schema::create('lead_remarks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('author_name')->nullable();
                $table->string('author_role', 40)->nullable();
                $table->text('remark');
                $table->string('call_status', 40)->nullable();  // called, no_answer, callback
                $table->timestamp('created_at')->useCurrent();

                $table->index(['lead_id', 'created_at']);
            });
        }

        // ── 8. visit_media (API field visit media) ────────────────────────────
        if (!Schema::hasTable('visit_media')) {
            Schema::create('visit_media', function (Blueprint $table) {
                $table->id();
                $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
                $table->enum('media_type', ['image', 'video', 'document'])->default('image');
                $table->string('file_path');
                $table->string('caption')->nullable();
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index('visit_id');
            });
        }

        // ── 9. payment_orders (Razorpay order tracking) ───────────────────────
        if (!Schema::hasTable('payment_orders')) {
            Schema::create('payment_orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('package_id')->nullable();
                $table->decimal('amount', 10, 2);
                $table->integer('credits')->default(0);
                $table->string('razorpay_order_id')->nullable()->unique();
                $table->string('razorpay_payment_id')->nullable();
                $table->string('razorpay_signature')->nullable();
                $table->enum('status', ['created', 'paid', 'failed', 'refunded'])->default('created');
                $table->text('failure_reason')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_orders');
        Schema::dropIfExists('visit_media');
        Schema::dropIfExists('lead_remarks');
        Schema::dropIfExists('pricing_settings');
        Schema::dropIfExists('lead_pricing_configs');
        Schema::dropIfExists('credit_transactions');
        Schema::dropIfExists('credit_wallets');
        Schema::dropIfExists('amenity_property');
        Schema::dropIfExists('user_activity_log');
    }
};
