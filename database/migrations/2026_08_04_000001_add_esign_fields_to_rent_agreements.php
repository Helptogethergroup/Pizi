<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rent_agreements', function (Blueprint $table) {
            // Download URL from Setu after signing completes
            $table->string('setu_download_url', 1000)->nullable()->after('esign_status');

            // Timestamps for tracking esign flow
            $table->timestamp('signature_initiated_at')->nullable()->after('setu_download_url');
            $table->timestamp('signature_completed_at')->nullable()->after('signature_initiated_at');
        });
    }

    public function down(): void
    {
        Schema::table('rent_agreements', function (Blueprint $table) {
            $table->dropColumn([
                'setu_download_url',
                'signature_initiated_at',
                'signature_completed_at',
            ]);
        });
    }
};