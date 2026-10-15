<?php

namespace App\Services;

use App\Models\ApiSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Session based tokens for the native owner app.
 *
 *   access token   v2.<payload>.<signature>   stateless, ~15 minutes, carries the session id
 *   refresh token  opaque random string       stored only as a hash, rotated on every use
 *
 * Because every access token names a server-side session, a session can be
 * revoked (logout, "sign out everywhere", stolen device) and stops working
 * on the very next request.
 */
class ApiTokenService
{
    /** Create a session for a freshly authenticated user and return its tokens. */
    public function issue(User $user, array $device, Request $request): array
    {
        $refresh = $this->newRefreshToken();

        $session = ApiSession::create([
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'refresh_hash' => hash('sha256', $refresh),
            'device_id' => $this->clip($device['device_id'] ?? null, 100),
            'device_name' => $this->clip($device['device_name'] ?? null, 150),
            'platform' => $this->clip($device['platform'] ?? null, 20),
            'app_version' => $this->clip($device['app_version'] ?? null, 20),
            'ip' => $request->ip(),
            'user_agent' => $this->clip($request->userAgent(), 255),
            'last_used_at' => now(),
            'refresh_expires_at' => now()->addDays(config('api_auth.refresh_ttl_days')),
        ]);

        return $this->payload($session, $refresh);
    }

    /**
     * Exchange a refresh token for a new pair. The old refresh token stops
     * working. If an already-used token shows up again, the session is
     * assumed to be copied and is revoked.
     *
     * @return array{session: ApiSession, tokens: array}|null
     */
    public function rotate(string $refreshToken, Request $request): ?array
    {
        $hash = hash('sha256', $refreshToken);

        return DB::transaction(function () use ($hash, $request) {
            $session = ApiSession::where('refresh_hash', $hash)->lockForUpdate()->first();

            if (!$session) {
                $replayed = ApiSession::where('previous_refresh_hash', $hash)->whereNull('revoked_at')->first();
                if ($replayed) {
                    $this->revoke($replayed, 'refresh_reuse');
                }
                return null;
            }

            if (!$session->isActive()) {
                return null;
            }

            $refresh = $this->newRefreshToken();
            $session->forceFill([
                'previous_refresh_hash' => $session->refresh_hash,
                'refresh_hash' => hash('sha256', $refresh),
                'refresh_expires_at' => now()->addDays(config('api_auth.refresh_ttl_days')),
                'last_used_at' => now(),
                'ip' => $request->ip(),
                'user_agent' => $this->clip($request->userAgent(), 255),
            ])->save();

            return ['session' => $session, 'tokens' => $this->payload($session, $refresh)];
        });
    }

    public function revoke(ApiSession $session, string $reason): void
    {
        if ($session->revoked_at === null) {
            $session->forceFill(['revoked_at' => now(), 'revoked_reason' => $reason])->save();
        }
    }

    /** Revoke every live session of a user, optionally keeping one. Returns how many. */
    public function revokeAll(int $userId, string $reason, ?int $exceptSessionId = null): int
    {
        $q = ApiSession::where('user_id', $userId)->whereNull('revoked_at');
        if ($exceptSessionId) {
            $q->where('id', '!=', $exceptSessionId);
        }

        return $q->update(['revoked_at' => now(), 'revoked_reason' => $reason]);
    }

    /**
     * Verify an access token's signature and expiry (not the session — the
     * caller checks that against the database).
     *
     * @return array{uid: int, sid: string, exp: int}|null
     */
    public function parseAccess(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3 || $parts[0] !== 'v2') {
            return null;
        }

        [, $body, $sig] = $parts;
        if (!hash_equals($this->sign($body), $sig)) {
            return null;
        }

        $claims = json_decode($this->b64d($body), true);
        if (!is_array($claims) || !isset($claims['uid'], $claims['sid'], $claims['exp'])) {
            return null;
        }

        return $claims;
    }

    public function accessIsExpired(array $claims): bool
    {
        return (int) $claims['exp'] < time();
    }

    private function payload(ApiSession $session, string $refresh): array
    {
        $ttl = (int) config('api_auth.access_ttl_seconds');
        $body = $this->b64e(json_encode([
            'uid' => $session->user_id,
            'sid' => $session->uuid,
            'iat' => time(),
            'exp' => time() + $ttl,
        ]));

        return [
            'access_token' => 'v2.' . $body . '.' . $this->sign($body),
            'refresh_token' => $refresh,
            'expires_in' => $ttl,
            'session_id' => $session->uuid,
        ];
    }

    private function newRefreshToken(): string
    {
        return $this->b64e(random_bytes(48));
    }

    // Domain-separated key: an app key leak elsewhere or a v1 token can't be replayed as v2.
    private function sign(string $body): string
    {
        $key = hash_hmac('sha256', 'pizi.api.access.v2', (string) config('app.key'), true);

        return $this->b64e(hash_hmac('sha256', $body, $key, true));
    }

    private function b64e(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private function b64d(string $s): string
    {
        return (string) base64_decode(strtr($s, '-_', '+/'), true);
    }

    private function clip(?string $v, int $max): ?string
    {
        return $v === null ? null : mb_substr(trim($v), 0, $max);
    }
}
