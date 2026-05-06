<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite doesn't support ALTER ENUM directly. We use a string column instead
        // and validate at the application layer (which we already do).
        // For MySQL, this would re-define the enum. For SQLite, we just convert to string.
        
        $driver = DB::getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite: change to string column
            Schema::table('leads', function (Blueprint $table) {
                $table->string('source', 30)->default('website')->change();
            });
        } else {
            // MySQL/PostgreSQL: redefine enum
            DB::statement("ALTER TABLE leads MODIFY COLUMN source ENUM('website','whatsapp','meta_ads','google_ads','referral','walk_in','offline_campaign','tele_inbound','manual') DEFAULT 'website'");
        }
        
        // Track who created the lead manually
        Schema::table('leads', function (Blueprint $table) {
            if (!Schema::hasColumn('leads', 'created_by_user_id')) {
                $table->foreignId('created_by_user_id')->nullable()
                    ->after('assigned_field_executive_id')
                    ->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (Schema::hasColumn('leads', 'created_by_user_id')) {
                $table->dropForeign(['created_by_user_id']);
                $table->dropColumn('created_by_user_id');
            }
        });
    }
};