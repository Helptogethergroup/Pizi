<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds missing users columns and creates property_managers pivot table.
 * Also extends role ENUM to include 'tenant' and 'pg_manager'.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Extend role ENUM ─────────────────────────────────────────────────
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role
                ENUM('admin','owner','telecaller','field_executive','seo_manager','pg_manager','tenant','guest')
                NOT NULL DEFAULT 'guest'");
        }

        // ── Add missing users columns ────────────────────────────────────────
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'signup_type')) {
                $table->string('signup_type', 30)->default('email')->after('avatar')
                    ->comment('email, otp, google');
            }
            if (!Schema::hasColumn('users', 'address')) {
                $table->text('address')->nullable()->after('signup_type');
            }
            if (!Schema::hasColumn('users', 'owner_id')) {
                // pg_manager's parent owner
                $table->foreignId('owner_id')->nullable()->after('address')
                    ->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('users', 'permissions')) {
                // JSON array of feature keys for pg_manager
                $table->json('permissions')->nullable()->after('owner_id');
            }
            if (!Schema::hasColumn('users', 'upi_id')) {
                $table->string('upi_id')->nullable()->after('permissions');
            }
            if (!Schema::hasColumn('users', 'dashboard_guide_seen_at')) {
                $table->timestamp('dashboard_guide_seen_at')->nullable()->after('upi_id');
            }
            if (!Schema::hasColumn('users', 'journey_stage')) {
                // Tenant onboarding step (1-5)
                $table->unsignedTinyInteger('journey_stage')->default(0)->after('dashboard_guide_seen_at');
            }
        });

        // ── property_managers pivot (owner → pg_manager → properties) ────────
        if (!Schema::hasTable('property_managers')) {
            Schema::create('property_managers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('manager_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('property_id')->constrained()->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['manager_id', 'property_id']);
                $table->index('owner_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('property_managers');

        Schema::table('users', function (Blueprint $table) {
            $cols = [
                'journey_stage', 'dashboard_guide_seen_at',
                'upi_id', 'permissions', 'owner_id',
                'address', 'signup_type',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('users', $col)) {
                    if ($col === 'owner_id') {
                        $table->dropForeign(['owner_id']);
                    }
                    $table->dropColumn($col);
                }
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role
                ENUM('admin','owner','telecaller','field_executive','seo_manager','guest')
                NOT NULL DEFAULT 'guest'");
        }
    }
};
