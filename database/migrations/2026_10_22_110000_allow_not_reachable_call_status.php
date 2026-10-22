<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The telecaller's "Not reachable" button saves call_status = 'not_reachable', but on some
 * databases leads.call_status is an ENUM that does not list it, so the save failed. Turn the
 * column into a plain string (existing values are kept) so every call outcome can be stored.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('leads', 'call_status') || DB::getDriverName() !== 'mysql') {
            return;
        }

        $col = DB::selectOne("SHOW COLUMNS FROM leads WHERE Field = 'call_status'");
        if ($col && stripos($col->Type, 'enum') === 0) {
            DB::statement("ALTER TABLE leads MODIFY call_status VARCHAR(50) NULL");
        }
    }

    public function down(): void
    {
        // Left as a string column: narrowing back to an enum could reject saved rows.
    }
};
