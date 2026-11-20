<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * When an owner adds a tenant (website or owner app), the tenant gets one WhatsApp message with the Play Store link
 * of the Pizi tenant app. At most one message per phone number, ever. It never blocks or breaks adding the tenant:
 * it is sent after the owner already has the answer, and any failure is only logged.
 * The admin switches it on (App Updates page) once the template tenant_app_invite is approved on Meta.
 */
class TenantAppInviteService
{
    public const TEMPLATE = 'tenant_app_invite';

    public static function enabled(): bool
    {
        $row = DB::table('app_settings')->where('key', 'tenant_invite_enabled')->first();

        return $row && (string) $row->value === '1';
    }

    public static function setEnabled(bool $on): void
    {
        DB::table('app_settings')->updateOrInsert(
            ['key' => 'tenant_invite_enabled'],
            ['value' => $on ? '1' : '0', 'updated_at' => now(), 'created_at' => now()]
        );
    }

    /** Send after the response has gone out, so the owner never waits for WhatsApp. */
    public static function queue(int $tenantId): void
    {
        app()->terminating(function () use ($tenantId) {
            app(self::class)->send($tenantId);
        });
    }

    /** @return string sent | off | skipped | already | failed */
    public function send(int $tenantId): string
    {
        try {
            if (!self::enabled()) {
                return 'off';
            }
            $tenant = DB::table('tenants')->where('id', $tenantId)->first();
            if (!$tenant || !$tenant->phone) {
                return 'skipped';
            }
            $phone = substr(preg_replace('/\D+/', '', (string) $tenant->phone), -10);
            if (strlen($phone) < 10) {
                return 'skipped';
            }
            // already uses the Pizi app on an Android phone (signed up there first): nothing to invite them to
            if ($tenant->user_id && DB::table('api_sessions')->where('user_id', $tenant->user_id)->where('platform', 'android')->exists()) {
                return 'skipped';
            }
            // one message per phone number, ever (the unique key also stops two requests at once from both sending)
            $reserved = DB::table('tenant_app_invites')->insertOrIgnore([
                'phone' => $phone, 'tenant_id' => $tenantId, 'sent_at' => now(),
            ]);
            if (!$reserved) {
                return 'already';
            }

            $result = app(WhatsAppService::class)->sendTemplate($phone, self::TEMPLATE, [trim((string) $tenant->name) ?: 'there']);
            if (empty($result['ok'])) {
                DB::table('tenant_app_invites')->where('phone', $phone)->delete(); // not sent: may be tried again next time
                Log::warning('Tenant app invite not sent', ['tenant_id' => $tenantId, 'result' => $result['message'] ?? null]);

                return 'failed';
            }

            return 'sent';
        } catch (\Throwable $e) {
            Log::warning('Tenant app invite error: ' . $e->getMessage(), ['tenant_id' => $tenantId]);

            return 'failed';
        }
    }
}
