<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('reviews'); // safety: drop stale table before recreating

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('reviewer_name')->nullable(); // For non-logged-in reviews
            $table->string('reviewer_phone')->nullable();
            
            // Ratings 1-5
            $table->tinyInteger('rating'); // Overall 1-5
            $table->tinyInteger('cleanliness')->nullable();
            $table->tinyInteger('food')->nullable();
            $table->tinyInteger('staff')->nullable();
            $table->tinyInteger('value_for_money')->nullable();
            $table->tinyInteger('amenities')->nullable();
            
            $table->string('title')->nullable();
            $table->text('comment');
            
            // Owner response
            $table->text('owner_response')->nullable();
            $table->timestamp('owner_responded_at')->nullable();
            
            // Moderation
            $table->enum('status', ['pending', 'approved', 'rejected', 'spam'])->default('pending');
            $table->boolean('is_verified_tenant')->default(false); // Bonus: tenant ka actual record hai
            $table->text('admin_notes')->nullable();
            
            // Helpful counts
            $table->integer('helpful_count')->default(0);
            $table->integer('not_helpful_count')->default(0);
            
            $table->timestamps();
            
            $table->index(['property_id', 'status']);
            $table->index('rating');
        });

        // Properties table me rating cache add karenge
        Schema::table('properties', function (Blueprint $table) {
            if (!Schema::hasColumn('properties', 'rating_avg')) {
                $table->decimal('rating_avg', 3, 2)->default(0)->after('view_count');
            }
            if (!Schema::hasColumn('properties', 'rating_count')) {
                $table->integer('rating_count')->default(0)->after('rating_avg');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn(['rating_avg', 'rating_count']);
        });
    }
};