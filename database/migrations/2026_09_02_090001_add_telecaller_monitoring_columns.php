<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (!Schema::hasColumn('leads', 'rejection_reason')) {
                $table->string('rejection_reason', 50)->nullable()->after('call_notes');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'daily_call_target')) {
                // Admin can set a per-telecaller daily call target. Null = use
                // the app-wide default (see config('telecaller.default_daily_call_target')).
                $table->unsignedInteger('daily_call_target')->nullable()->after('role');
            }
        });

        if (!Schema::hasTable('lead_assignment_logs')) {
            Schema::create('lead_assignment_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
                $table->foreignId('from_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('to_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('reason', 255)->nullable();
                $table->timestamp('created_at')->nullable();

                $table->index(['lead_id', 'created_at']);
                $table->index('to_user_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_assignment_logs');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'daily_call_target')) {
                $table->dropColumn('daily_call_target');
            }
        });

        Schema::table('leads', function (Blueprint $table) {
            if (Schema::hasColumn('leads', 'rejection_reason')) {
                $table->dropColumn('rejection_reason');
            }
        });
    }
};
