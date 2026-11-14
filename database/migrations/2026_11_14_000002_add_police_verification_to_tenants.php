<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Police verification of a tenant: 'pending' until a document has been uploaded (by the owner when adding
 * the tenant, or later by the owner or the tenant), then 'done'. Both the owner and the tenant see it.
 *
 * The police document is stored as a tenant document, so document_type becomes a plain string: it was an
 * enum on some servers and could not hold a new kind of document.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tenants') && !Schema::hasColumn('tenants', 'police_verification_status')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->string('police_verification_status', 20)->default('pending');
            });
        }

        if (Schema::hasTable('tenant_documents') && DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE tenant_documents MODIFY document_type VARCHAR(50) NOT NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tenants', 'police_verification_status')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->dropColumn('police_verification_status');
            });
        }
        // document_type stays a string: narrowing it again could make existing rows invalid
    }
};
