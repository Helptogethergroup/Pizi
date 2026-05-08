<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL me enum modify karne ka safe way
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','owner','telecaller','field_executive','seo_manager','guest') NOT NULL DEFAULT 'guest'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','owner','telecaller','field_executive','guest') NOT NULL DEFAULT 'guest'");
    }
};