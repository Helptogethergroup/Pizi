<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Credit packages — admin-defined plans
        Schema::create('credit_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');                    // "Starter Pack"
            $table->integer('price_inr');              // ₹999 stored as 999
            $table->integer('credits');                // 50 base credits
            $table->integer('bonus_credits')->default(0);  // 10 free bonus
            $table->boolean('is_popular')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Payments — every Razorpay transaction
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('credit_package_id')->nullable()->constrained()->nullOnDelete();
            
            $table->integer('amount_inr');             // ₹999 stored as 999
            $table->integer('credits_to_add');         // total credits user gets (base + bonus)
            
            $table->string('razorpay_order_id')->unique();
            $table->string('razorpay_payment_id')->nullable();
            $table->string('razorpay_signature')->nullable();
            
            $table->enum('status', ['created', 'paid', 'failed', 'refunded'])
                ->default('created')->index();
            
            $table->json('payload')->nullable();       // full razorpay response
            $table->text('failure_reason')->nullable();
            
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('credit_packages');
    }
};