<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (!Schema::hasColumn('leads', 'inquiry_type')) {
                // 'unknown' default — existing behaviour (all leads treated as
                // tenant enquiries) doesn't change; this only adds NEW info,
                // it never restricts who can see a lead.
                $table->enum('inquiry_type', ['tenant', 'owner', 'unknown'])
                    ->default('unknown')
                    ->after('source');
            }
        });

        // Backfill existing rows with a best-guess, without touching
        // anything else about them:
        // - tied to a specific property → someone enquiring to rent it → tenant
        // - has tenant-side search preferences filled in → tenant
        // - everything else (generic contact-us messages etc.) → stays 'unknown'
        DB::table('leads')->whereNotNull('property_id')->update(['inquiry_type' => 'tenant']);
        DB::table('leads')
            ->whereNull('property_id')
            ->where('inquiry_type', 'unknown')
            ->where(function ($q) {
                $q->whereNotNull('preferred_city')
                  ->orWhereNotNull('preferred_locality')
                  ->orWhereNotNull('budget_min')
                  ->orWhereNotNull('budget_max');
            })
            ->update(['inquiry_type' => 'tenant']);
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (Schema::hasColumn('leads', 'inquiry_type')) {
                $table->dropColumn('inquiry_type');
            }
        });
    }
};
