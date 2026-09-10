<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds columns that exist in Models/$fillable but are missing from their tables:
 *  - leads: created_by_user_id, call_status, called_at, call_attempts, call_notes, user_id, credit_cost
 *  - visits: completed_at (used in field exec / admin dashboards)
 *  - users: guide_dismissed (owner onboarding guide)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── leads: extra columns added over time ─────────────────────────────
        Schema::table('leads', function (Blueprint $table) {
            if (!Schema::hasColumn('leads', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('property_id')
                    ->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('leads', 'created_by_user_id')) {
                $table->foreignId('created_by_user_id')->nullable()->after('user_id')
                    ->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('leads', 'call_status')) {
                $table->string('call_status', 50)->nullable()->after('status')
                    ->comment('called, no_answer, callback, wrong_number');
            }
            if (!Schema::hasColumn('leads', 'called_at')) {
                $table->timestamp('called_at')->nullable()->after('call_status');
            }
            if (!Schema::hasColumn('leads', 'call_attempts')) {
                $table->unsignedTinyInteger('call_attempts')->default(0)->after('called_at');
            }
            if (!Schema::hasColumn('leads', 'call_notes')) {
                $table->text('call_notes')->nullable()->after('call_attempts');
            }
            if (!Schema::hasColumn('leads', 'credit_cost')) {
                $table->integer('credit_cost')->default(0)->after('call_notes');
            }
        });

        // ── visits: completed_at column ──────────────────────────────────────
        Schema::table('visits', function (Blueprint $table) {
            if (!Schema::hasColumn('visits', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('checked_out_at');
            }
            if (!Schema::hasColumn('visits', 'status')) {
                $table->string('status', 30)->default('scheduled')->after('outcome');
            }
        });

        // ── users: guide_dismissed (owner onboarding) ────────────────────────
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'guide_dismissed')) {
                $table->boolean('guide_dismissed')->default(false)->after('is_active');
            }
            if (!Schema::hasColumn('users', 'avatar')) {
                $table->string('avatar')->nullable()->after('name');
            }
            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('guide_dismissed');
            }
            if (!Schema::hasColumn('users', 'last_login_ip')) {
                $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(array_filter([
                Schema::hasColumn('users', 'last_login_ip')    ? 'last_login_ip'    : null,
                Schema::hasColumn('users', 'last_login_at')    ? 'last_login_at'    : null,
                Schema::hasColumn('users', 'guide_dismissed')  ? 'guide_dismissed'  : null,
                Schema::hasColumn('users', 'avatar')           ? 'avatar'           : null,
            ]));
        });

        Schema::table('visits', function (Blueprint $table) {
            $table->dropColumn(array_filter([
                Schema::hasColumn('visits', 'status')       ? 'status'       : null,
                Schema::hasColumn('visits', 'completed_at') ? 'completed_at' : null,
            ]));
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(array_filter([
                Schema::hasColumn('leads', 'credit_cost')         ? 'credit_cost'         : null,
                Schema::hasColumn('leads', 'call_notes')          ? 'call_notes'          : null,
                Schema::hasColumn('leads', 'call_attempts')       ? 'call_attempts'       : null,
                Schema::hasColumn('leads', 'called_at')           ? 'called_at'           : null,
                Schema::hasColumn('leads', 'call_status')         ? 'call_status'         : null,
                Schema::hasColumn('leads', 'created_by_user_id')  ? 'created_by_user_id'  : null,
                Schema::hasColumn('leads', 'user_id')             ? 'user_id'             : null,
            ]));
        });
    }
};
