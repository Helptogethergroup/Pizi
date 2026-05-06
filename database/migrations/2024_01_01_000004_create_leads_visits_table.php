<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_telecaller_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_field_executive_id')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->string('name');
            $table->string('phone', 15)->index();
            $table->string('email')->nullable();
            $table->string('preferred_locality')->nullable();
            $table->string('preferred_city')->nullable();
            $table->enum('preferred_gender', ['male', 'female', 'unisex'])->nullable();
            $table->decimal('budget_min', 10, 2)->nullable();
            $table->decimal('budget_max', 10, 2)->nullable();
            $table->date('move_in_date')->nullable();
            $table->text('message')->nullable();

            $table->enum('source', ['website', 'whatsapp', 'meta_ads', 'google_ads', 'referral', 'walk_in'])
                ->default('website')->index();

            $table->enum('status', [
                'new', 'contacted', 'interested', 'follow_up',
                'visit_scheduled', 'visit_done', 'closed_won', 'closed_lost', 'junk'
            ])->default('new')->index();

            $table->text('telecaller_notes')->nullable();
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamp('next_follow_up_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
        });

        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->restrictOnDelete();
            $table->foreignId('field_executive_id')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->dateTime('scheduled_at');
            $table->dateTime('checked_in_at')->nullable();
            $table->dateTime('checked_out_at')->nullable();
            $table->decimal('checkin_lat', 10, 7)->nullable();
            $table->decimal('checkin_lng', 10, 7)->nullable();

            $table->enum('outcome', ['pending', 'closed', 'rejected', 'revisit', 'no_show'])
                ->default('pending')->index();
            $table->decimal('token_amount', 10, 2)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reviewer_name');
            $table->unsignedTinyInteger('rating'); // 1-5
            $table->text('comment')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->timestamps();
        });

        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'property_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlists');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('visits');
        Schema::dropIfExists('leads');
    }
};
