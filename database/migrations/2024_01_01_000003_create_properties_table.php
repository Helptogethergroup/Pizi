<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('amenities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->nullable(); // SVG path or class name
            $table->timestamps();
        });

        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('city_id')->constrained()->restrictOnDelete();
            $table->foreignId('locality_id')->constrained()->restrictOnDelete();

            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('rules')->nullable();

            $table->enum('gender', ['male', 'female', 'unisex'])->default('unisex')->index();
            $table->enum('property_type', ['pg', 'hostel', 'coliving', 'flatmate'])
                ->default('pg')->index();

            // Pricing
            $table->decimal('rent_min', 10, 2);
            $table->decimal('rent_max', 10, 2);
            $table->decimal('security_deposit', 10, 2)->default(0);
            $table->boolean('food_included')->default(false);

            // Sharing options (stored as JSON)
            // e.g., {"single": 12000, "double": 8000, "triple": 6000}
            $table->json('sharing_options')->nullable();

            // Address
            $table->string('address_line');
            $table->string('landmark')->nullable();
            $table->string('pincode', 10)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Status & inventory
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_verified')->default(false)->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->integer('total_rooms')->default(0);
            $table->integer('available_rooms')->default(0);

            // SEO
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->string('cover_image')->nullable();

            // Stats
            $table->unsignedBigInteger('view_count')->default(0);
            $table->unsignedBigInteger('lead_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['city_id', 'locality_id', 'is_active']);
            $table->index(['gender', 'is_active']);
        });

        Schema::create('property_amenities', function (Blueprint $table) {
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('amenity_id')->constrained()->cascadeOnDelete();
            $table->primary(['property_id', 'amenity_id']);
        });

        Schema::create('property_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('image_path');
            $table->string('caption')->nullable();
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_images');
        Schema::dropIfExists('property_amenities');
        Schema::dropIfExists('properties');
        Schema::dropIfExists('amenities');
    }
};
