<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Tenants get one WhatsApp message with the Play Store link of the Pizi tenant app:
 *  - right after an owner adds them (website or owner app), and
 *  - for tenants added before this existed, a batch a day (tenant-app:invite-existing) until all are done.
 * At most one message per phone number, ever. It never blocks or breaks adding a tenant: the first kind is sent
 * after the owner already has the answer, and any failure is only logged.
 * The admin switches both on (App Updates page) once the template tenant_app_invite is approved on Meta.
 */
class TenantAppInviteService
{
    public const TEMPLATE = 'tenant_app_invite';
    public const MAX_ATTEMPTS = 3;

    private static function flag(string $key): bool
    {
        $row = DB::table('app_settings')->where('key', $key)->first();

        return $row && (string) $row->value === '1';
    }

    private static function setFlag(string $key, bool $on): void
    {
        DB::table('app_settings')->updateOrInsert(['key' => $key], ['value' => $on ? '1' : '0', 'updated_at' => now(), 'created_at' => now()]);
    }

    /** New tenants are messaged. */
    public static function enabled(): bool
    {
        return self::flag('tenant_invite_enabled');
    }

    public static function setEnabled(bool $on): void
    {
        self::setFlag('tenant_invite_enabled', $on);
    }

    /** Tenants added earlier are messaged too (a daily batch). Only runs when new-tenant messages are on as well. */
    public static function backfillEnabled(): bool
    {
        return self::flag('tenant_invite_backfill');
    }

    public static function setBackfillEnabled(bool $on): void
    {
        self::setFlag('tenant_invite_backfill', $on);
    }

    public static function batchSize(): int
    {
        $row = DB::table('app_settings')->where('key', 'tenant_invite_batch')->first();

        return max(1, min(2000, (int) ($row->value ?? 200) ?: 200));
    }

    public static function setBatchSize(int $n): void
    {
        DB::table('app_settings')->updateOrInsert(['key' => 'tenant_invite_batch'], ['value' => (string) $n, 'updated_at' => now(), 'created_at' => now()]);
    }

    /** Send after the response has gone out, so the owner never waits for WhatsApp. */
    public static function queue(int $tenantId): void
    {
        app()->terminating(function () use ($tenantId) {
            app(self::class)->send($tenantId);
        });
    }

    /**
     * Tenants who are living in a PG now (active or on notice) and have not been invited yet: one per phone number,
     * a usable phone, not already on the Android app, and not a number WhatsApp keeps refusing.
     *
     * @return \Illuminate\Support\Collection<int, object{id:int,phone10:string}>
     */
    public function pendingExisting(): \Illuminate\Support\Collection
    {
        $done = DB::table('tenant_app_invites')
            ->where(fn ($q) => $q->where('status', 'sent')->orWhere('attempts', '>=', self::MAX_ATTEMPTS))
            ->pluck('phone')->flip();
        $onApp = DB::table('api_sessions')->where('platform', 'android')->distinct()->pluck('user_id')->flip();

        $seen = [];
        $out = collect();
        foreach (DB::table('tenants')->whereIn('status', ['active', 'notice_period'])->whereNotNull('phone')->orderBy('id')->get(['id', 'phone', 'user_id']) as $t) {
            $phone = substr(preg_replace('/\D+/', '', (string) $t->phone), -10);
            if (strlen($phone) < 10 || isset($seen[$phone]) || $done->has($phone) || ($t->user_id && $onApp->has($t->user_id))) {
                continue;
            }
            $seen[$phone] = true;
            $out->push((object) ['id' => (int) $t->id, 'phone10' => $phone]);
        }

        return $out;
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

            // One message per phone number, ever. The unique key also stops two requests at once from both sending;
            // a number that failed before is tried again, up to MAX_ATTEMPTS times in all.
            $inserted = DB::table('tenant_app_invites')->insertOrIgnore([
                'phone' => $phone, 'tenant_id' => $tenantId, 'status' => 'sending', 'attempts' => 1, 'sent_at' => now(),
            ]);
            if (!$inserted) {
                $retry = DB::table('tenant_app_invites')->where('phone', $phone)->where('status', 'failed')
                    ->where('attempts', '<', self::MAX_ATTEMPTS)
                    ->update(['status' => 'sending', 'attempts' => DB::raw('attempts + 1'), 'tenant_id' => $tenantId]);
                if (!$retry) {
                    return DB::table('tenant_app_invites')->where('phone', $phone)->where('status', 'failed')->exists() ? 'skipped' : 'already';
                }
            }

            $result = app(WhatsAppService::class)->sendTemplate($phone, self::TEMPLATE, [trim((string) $tenant->name) ?: 'there']);
            if (empty($result['ok'])) {
                DB::table('tenant_app_invites')->where('phone', $phone)->update(['status' => 'failed']);
                Log::warning('Tenant app invite not sent', ['tenant_id' => $tenantId, 'result' => $result['message'] ?? null]);

                return 'failed';
            }
            DB::table('tenant_app_invites')->where('phone', $phone)->update(['status' => 'sent', 'sent_at' => now()]);

            return 'sent';
        } catch (\Throwable $e) {
            Log::warning('Tenant app invite error: ' . $e->getMessage(), ['tenant_id' => $tenantId]);

            return 'failed';
        }
    }
}
