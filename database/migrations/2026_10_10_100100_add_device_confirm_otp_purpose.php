<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // New purpose for the "confirm this new device" OTP of the native owner app,
    // kept apart from login / registration codes so one can never be used for the other.
    public function up(): void
    {
        $this->alterPurpose("'login','register','password_reset','phone_verify','email_verify','device_confirm'");
    }

    public function down(): void
    {
        DB::table('otp_verifications')->where('purpose', 'device_confirm')->delete();
        $this->alterPurpose("'login','register','password_reset','phone_verify','email_verify'");
    }

    private function alterPurpose(string $values): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // The table was created years of schema ago with a zero-date default on
        // `expires_at`; strict mode refuses to rewrite the table because of it.
        // Relax the mode for this one statement only, then put it back.
        $previous = DB::selectOne('SELECT @@SESSION.sql_mode AS m')->m;
        DB::statement("SET SESSION sql_mode = ''");

        try {
            DB::statement("ALTER TABLE otp_verifications MODIFY purpose ENUM($values) NOT NULL DEFAULT 'login'");
        } finally {
            DB::statement('SET SESSION sql_mode = ?', [$previous]);
        }
    }
};
