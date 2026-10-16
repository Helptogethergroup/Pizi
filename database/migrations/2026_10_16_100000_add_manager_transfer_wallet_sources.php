<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * An owner can hand credits to a PG Manager (and take them back). Those moves
 * need their own labels in the wallet history.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE wallet_transactions MODIFY COLUMN source ENUM(
            'admin_credit','admin_debit','purchase','lead_unlock','refund','bonus','expiry',
            'manager_transfer_in','manager_transfer_out'
        ) NOT NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE wallet_transactions MODIFY COLUMN source ENUM(
            'admin_credit','admin_debit','purchase','lead_unlock','refund','bonus','expiry'
        ) NOT NULL");
    }
};
