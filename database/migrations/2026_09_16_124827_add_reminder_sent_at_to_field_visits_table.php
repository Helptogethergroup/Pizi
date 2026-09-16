<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tracks whether the "visit starting soon" reminder has already gone
     * out for this visit, so the scheduled command doesn't notify the
     * field executive twice for the same visit.
     */
    public function up(): void
    {
        Schema::table('field_visits', function (Blueprint $table) {
            $table->timestamp('reminder_sent_at')->nullable()->after('remarks');
        });
    }

    public function down(): void
    {
        Schema::table('field_visits', function (Blueprint $table) {
            $table->dropColumn('reminder_sent_at');
        });
    }
};
