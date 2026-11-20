<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * "Please update the Pizi Owner app" reminders.
 *
 * The owner app sends its version with every request (X-App-Version, e.g. "1.2.2+24") and the server keeps the
 * newest one on the owner's session. An owner is "out of date" when the best version on any of their active
 * Android sessions is older than the latest version the admin set. They get one WhatsApp message a day until
 * that is no longer true: the moment they update and open the app, the message stops by itself.
 */
class OwnerAppUpdateService
{
    // ── settings (admin panel) ───────────────────────────────────────────

    public function setting(string $key, $default = null)
    {
        $row = DB::table('app_settings')->where('key', $key)->first();

        return $row && $row->value !== null && $row->value !== '' ? $row->value : $default;
    }

    public function setSetting(string $key, $value): void
    {
        DB::table('app_settings')->updateOrInsert(
            ['key' => $key],
            ['value' => (string) $value, 'updated_at' => now(), 'created_at' => now()]
        );
    }

    public function enabled(): bool
    {
        return (string) $this->setting('owner_update_reminders', '0') === '1';
    }

    /** The version owners should be on, "1.2.2+24" or "1.2.2". Empty = nothing to remind about. */
    public function latestVersion(): ?string
    {
        $v = trim((string) $this->setting('owner_app_latest_version', ''));

        return $v === '' ? null : $v;
    }

    /** No owner gets more than this many reminders in total (so nobody is messaged forever). */
    public function maxReminders(): int
    {
        return max(1, (int) $this->setting('owner_update_max_reminders', 30));
    }

    // ── versions ─────────────────────────────────────────────────────────

    /** "1.2.2+24" => ['1.2.2', 24]; "1.2.1" => ['1.2.1', 0]; null or junk => ['0', 0]. */
    public static function parse(?string $v): array
    {
        $v = trim((string) $v);
        if (!preg_match('/^(\d+(?:\.\d+){0,3})(?:\+(\d+))?/', $v, $m)) {
            return ['0', 0];
        }

        return [$m[1], isset($m[2]) ? (int) $m[2] : 0];
    }

    /** Negative when $a is older than $b, 0 when equal, positive when newer. Version first, then the build number. */
    public static function compare(?string $a, ?string $b): int
    {
        [$va, $ba] = self::parse($a);
        [$vb, $bb] = self::parse($b);
        $c = version_compare($va, $vb);

        return $c !== 0 ? $c : $ba <=> $bb;
    }

    // ── who is out of date ───────────────────────────────────────────────

    /**
     * Owners with an active Android session, and the best app version among those sessions.
     *
     * @return \Illuminate\Support\Collection<int, object{user_id:int,name:string,phone:?string,version:?string}>
     */
    public function owners(): \Illuminate\Support\Collection
    {
        $rows = DB::table('api_sessions as s')
            ->join('users as u', 'u.id', '=', 's.user_id')
            ->where('u.role', 'owner')
            ->where('u.is_active', true)
            ->whereNull('s.revoked_at')
            ->where('s.refresh_expires_at', '>', now())
            ->where('s.platform', 'android')
            ->get(['u.id as user_id', 'u.name', 'u.phone', 's.app_version']);

        return $rows->groupBy('user_id')->map(function ($sessions) {
            $best = null;
            foreach ($sessions as $s) {
                if ($best === null || self::compare($s->app_version, $best) > 0) {
                    $best = $s->app_version;
                }
            }
            $first = $sessions->first();

            return (object) ['user_id' => (int) $first->user_id, 'name' => $first->name, 'phone' => $first->phone, 'version' => $best];
        })->values();
    }

    /** Owners whose best version is older than the latest. */
    public function outdated(?string $latest = null): \Illuminate\Support\Collection
    {
        $latest ??= $this->latestVersion();
        if ($latest === null) {
            return collect();
        }

        return $this->owners()->filter(fn ($o) => self::compare($o->version, $latest) < 0)->values();
    }

    // ── owners who never installed the app ───────────────────────────────

    /** Marker stored in owner_app_update_reminders.latest_version for "download the app" messages. */
    public const INSTALL_KIND = 'install';

    public function installEnabled(): bool
    {
        return (string) $this->setting('owner_install_reminders', '0') === '1';
    }

    /** No owner gets more than this many "download the app" messages in total. */
    public function installMax(): int
    {
        return max(1, (int) $this->setting('owner_install_max_reminders', 30));
    }

    /**
     * Active owners who have never signed in to the Android owner app (no Android session at all, even a signed-out
     * one) and have a usable phone number. Rebuilt on every call, so new owners join the list and an owner who
     * installs and signs in drops out by themselves.
     */
    public function installTargets(): \Illuminate\Support\Collection
    {
        return DB::table('users as u')
            ->where('u.role', 'owner')
            ->where('u.is_active', true)
            ->whereNotNull('u.phone')
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('api_sessions as s')
                ->whereColumn('s.user_id', 'u.id')->where('s.platform', 'android'))
            ->get(['u.id as user_id', 'u.name', 'u.phone'])
            ->filter(fn ($o) => strlen(preg_replace('/\D+/', '', (string) $o->phone)) >= 10)
            ->values();
    }

    /** Numbers for the admin page. */
    public function counts(): array
    {
        $latest = $this->latestVersion();
        $owners = $this->owners();
        $outdated = $latest ? $owners->filter(fn ($o) => self::compare($o->version, $latest) < 0)->count() : 0;

        return [
            'owners_on_app' => $owners->count(),
            'outdated' => $outdated,
            'updated' => $latest ? $owners->count() - $outdated : 0,
            'sent_today' => (int) DB::table('owner_app_update_reminders')->where('latest_version', '!=', self::INSTALL_KIND)->whereDate('sent_at', today())->count(),
            'sent_total' => (int) DB::table('owner_app_update_reminders')->where('latest_version', '!=', self::INSTALL_KIND)->count(),
            'no_app' => $this->installTargets()->count(),
            'install_sent_today' => (int) DB::table('owner_app_update_reminders')->where('latest_version', self::INSTALL_KIND)->whereDate('sent_at', today())->count(),
            'install_sent_total' => (int) DB::table('owner_app_update_reminders')->where('latest_version', self::INSTALL_KIND)->count(),
        ];
    }
}
