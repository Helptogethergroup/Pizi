<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            // Receipt for token collection
            if (!Schema::hasColumn('visits', 'receipt_image')) {
                $table->string('receipt_image')->nullable()->after('token_amount');
            }
            
            // Tenant feedback at site
            if (!Schema::hasColumn('visits', 'tenant_feedback')) {
                $table->text('tenant_feedback')->nullable()->after('notes');
            }
            
            // Reason for rejection (if outcome = rejected)
            if (!Schema::hasColumn('visits', 'rejection_reason')) {
                $table->string('rejection_reason')->nullable()->after('tenant_feedback');
            }
            
            // Distance from property at check-in (geo-fence proof)
            if (!Schema::hasColumn('visits', 'checkin_distance_m')) {
                $table->integer('checkin_distance_m')->nullable()->after('checkin_lng');
            }
        });
    }

    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropColumn(['receipt_image', 'tenant_feedback', 'rejection_reason', 'checkin_distance_m']);
        });
    }
};