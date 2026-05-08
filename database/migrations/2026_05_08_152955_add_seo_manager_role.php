<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite has no real ENUM — values are stored as strings.
        // For MySQL, we modify the enum to include seo_manager.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','owner','telecaller','field_executive','seo_manager','guest') NOT NULL DEFAULT 'guest'");
        }
        // For SQLite — no schema change needed, role accepts any string value.
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','owner','telecaller','field_executive','guest') NOT NULL DEFAULT 'guest'");
        }
    }
};