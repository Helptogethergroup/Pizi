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
        Schema::table('users', function (Blueprint $table) {
            // Marks internal QA/testing owner accounts. When such an
            // account "unlocks" a lead to test the flow, that lead must
            // NOT disappear from the shared pool for real owners — see
            // LeadMatchingService::leadsForOwner().
            $table->boolean('is_test_account')->default(false)->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_test_account');
        });
    }
};
