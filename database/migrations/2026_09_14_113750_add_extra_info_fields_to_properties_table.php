<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->enum('food_type', ['veg', 'non_veg', 'both'])->nullable()->after('food_included');
            $table->string('food_timing', 255)->nullable()->after('food_type');
            $table->unsignedSmallInteger('construction_year')->nullable()->after('food_timing');
            $table->boolean('pet_allowed')->nullable()->after('construction_year');
            $table->boolean('guest_entry_allowed')->nullable()->after('pet_allowed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn(['food_type', 'food_timing', 'construction_year', 'pet_allowed', 'guest_entry_allowed']);
        });
    }
};
