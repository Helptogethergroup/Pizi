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
            // Which leads a telecaller is routed — tenant enquiries, owner
            // onboarding calls, or both. Only meaningful for role=telecaller.
            $table->enum('lead_specialization', ['both', 'tenant', 'owner'])
                ->default('both')
                ->after('daily_call_target');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('lead_specialization');
        });
    }
};
