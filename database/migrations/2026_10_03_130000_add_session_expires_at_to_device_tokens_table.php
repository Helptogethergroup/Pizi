<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_tokens', function (Blueprint $table) {
            // When the login session this device registered under expires. After
            // that the app is auto-logged-out (no DELETE call), so we stop pushing.
            $table->timestamp('session_expires_at')->nullable()->after('platform');
        });
    }

    public function down(): void
    {
        Schema::table('device_tokens', function (Blueprint $table) {
            $table->dropColumn('session_expires_at');
        });
    }
};
