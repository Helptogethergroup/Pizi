<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Universities ─────────────────────────────────────────────────────
        if (!Schema::hasTable('universities')) {
            Schema::create('universities', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('short_name')->nullable();   // e.g. "DTU", "YMCA"
                $table->string('city', 100)->nullable();
                $table->string('state', 100)->nullable();
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->string('website')->nullable();
                $table->string('image')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index('city');
            });
        }

        // ── Add nearby_university_id to properties ───────────────────────────
        if (!Schema::hasColumn('properties', 'nearby_university_id')) {
            Schema::table('properties', function (Blueprint $table) {
                $table->foreignId('nearby_university_id')
                    ->nullable()
                    ->after('longitude')
                    ->constrained('universities')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('properties', 'nearby_university_id')) {
            Schema::table('properties', function (Blueprint $table) {
                $table->dropForeign(['nearby_university_id']);
                $table->dropColumn('nearby_university_id');
            });
        }

        Schema::dropIfExists('universities');
    }
};
