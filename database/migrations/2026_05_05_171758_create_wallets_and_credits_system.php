<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Wallets — har owner ka credit balance
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->integer('balance')->default(0);          // current credits
            $table->integer('lifetime_added')->default(0);   // total ever added
            $table->integer('lifetime_spent')->default(0);   // total ever spent
            $table->timestamps();
        });

        // Wallet transactions — audit log of EVERY credit movement
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['credit', 'debit']);
            $table->integer('amount');                       // positive number
            $table->integer('balance_after');                // wallet balance after this txn
            $table->enum('source', [
                'admin_credit',      // admin manually added
                'admin_debit',       // admin manually removed
                'purchase',          // owner purchased credits
                'lead_unlock',       // spent unlocking a lead
                'refund',            // admin refunded
                'bonus',             // promotional bonus
                'expiry',            // credits expired
            ]);
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference')->nullable();         // razorpay order id, etc.
            $table->text('notes')->nullable();
            $table->foreignId('actioned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        // Lead pricing config — admin sets prices per lead type
        Schema::create('lead_pricing', function (Blueprint $table) {
            $table->id();
            $table->enum('lead_type', ['direct', 'verified', 'converted', 'manual']);
            $table->integer('credit_cost');                  // credits to unlock
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Lead unlocks — tracks which owner unlocked which lead (lock mechanism)
        Schema::create('lead_unlocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // owner who unlocked
            $table->integer('credits_spent');
            $table->string('lead_type_at_unlock');           // snapshot of type
            $table->timestamps();

            $table->unique(['lead_id', 'user_id']); // owner can't unlock same lead twice
            $table->index('lead_id');
        });

        // Add lead_type column to existing leads table
        Schema::table('leads', function (Blueprint $table) {
            $table->enum('lead_type', ['direct', 'verified', 'converted', 'manual'])
                ->default('direct')->after('source')->index();
            $table->boolean('is_locked')->default(false)->after('lead_type');
            $table->foreignId('locked_by_user_id')->nullable()
                ->after('is_locked')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['lead_type', 'is_locked', 'locked_by_user_id']);
        });
        Schema::dropIfExists('lead_unlocks');
        Schema::dropIfExists('lead_pricing');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');
    }
};