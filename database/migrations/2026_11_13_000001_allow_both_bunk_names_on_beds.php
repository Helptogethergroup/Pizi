<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The beds.bed_type column was created with bunk_lower / bunk_upper, but the website form and
 * validation send bunk_top / bunk_bottom (and the app's names are mapped to those). Whichever
 * pair the database had, the other pair failed with a "data truncated" 500 error on Add Bed.
 * The column now accepts all four names, so every existing row stays valid.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('beds') || DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE beds MODIFY bed_type ENUM('single','bunk_top','bunk_bottom','bunk_lower','bunk_upper','double') NOT NULL DEFAULT 'single'");
    }

    public function down(): void
    {
        // Left as is: narrowing the list again could make existing rows invalid.
    }
};
