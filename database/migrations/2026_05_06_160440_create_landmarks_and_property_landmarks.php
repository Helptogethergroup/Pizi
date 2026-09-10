<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Landmarks (colleges, hospitals, metros, IT parks, malls)
        Schema::create('landmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->foreignId('locality_id')->nullable()->constrained()->nullOnDelete();
            
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('type', ['college', 'university', 'hospital', 'metro', 'office', 'mall', 'airport', 'railway']);
            
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            
            $table->text('description')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 320)->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);
            $table->unsignedBigInteger('view_count')->default(0);
            
            $table->timestamps();
            
            $table->index(['type', 'is_active']);
            $table->index(['city_id', 'is_active']);
        });

        // Property ↔ Landmark distance (cached for performance)
        Schema::create('property_landmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('landmark_id')->constrained()->cascadeOnDelete();
            $table->decimal('distance_km', 6, 2);
            $table->timestamps();

            $table->unique(['property_id', 'landmark_id']);
            $table->index(['landmark_id', 'distance_km']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_landmarks');
        Schema::dropIfExists('landmarks');
    }
};