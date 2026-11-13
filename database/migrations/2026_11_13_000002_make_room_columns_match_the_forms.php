<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * rooms.status was created as ('available','full','maintenance') but the website and the app save
 * 'active' / 'maintenance' / 'closed', and rent / deposit were NOT NULL while the forms may send
 * nothing. Depending on how a server's database was built, saving a room could fail with a 500.
 * The status column now knows every name (existing rows stay valid) and the amounts accept null.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('rooms') || DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE rooms MODIFY status ENUM('active','maintenance','closed','available','full') NOT NULL DEFAULT 'active'");
        DB::statement('ALTER TABLE rooms MODIFY monthly_rent DECIMAL(10,2) NULL DEFAULT 0');
        DB::statement('ALTER TABLE rooms MODIFY security_deposit DECIMAL(10,2) NULL DEFAULT 0');

        if (Schema::hasTable('beds')) {
            DB::statement('ALTER TABLE beds MODIFY monthly_rent DECIMAL(10,2) NULL DEFAULT 0');
        }
    }

    public function down(): void
    {
        // Left as is: narrowing the columns again could make existing rows invalid.
    }
};
