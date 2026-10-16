<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiSession;
use App\Models\User;
use App\Services\ApiTokenService;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Authentication for the native owner app (/api/v2/auth/*).
 *
 *  - password login, with a second step (OTP) the first time a device is seen
 *  - OTP login
 *  - short lived access tokens + rotating refresh tokens, revocable server side
 *  - lockout and rate limits; identical answers for unknown accounts and wrong passwords
 */
class AuthV2Controller extends Controller
{
    private const GENERIC_FAIL = 'The details you entered are incorrect.';

    public function __construct(
        private ApiTokenService $tokens,
        private OtpService $otp,
    ) {
    }

    // ── Password login ──────────────────────────────────────────────────────

    public function loginPassword(Request $request): JsonResponse
    {
        $data = $this->validated($request, [
            'identifier' => 'required|string|max:120',
            'password' => 'required|string|max:200',
            'device_id' => 'required|string|max:100',
        ]);
        if ($data instanceof JsonResponse) {
            return $data;
        }

        [$identifier, $type] = $this->normalize($data['identifier']);

        if ($blocked = $this->blockedResponse($request, $identifier)) {
            return $blocked;
        }

        $user = $this->findOwner($identifier, $type);

        // Same work whether or not the account exists, so timing doesn't leak it.
        $hash = $user?->password ?: '$2y$12$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';
        $passwordOk = Hash::check($data['password'], $hash);

        if (!$user || !$passwordOk || !$user->is_active) {
            $this->recordFailure($identifier);
            $this->audit($user?->id, 'api_login_failed', 'Password login failed (' . $request->ip() . ')');

            return $this->fail(self::GENERIC_FAIL, 401, 'invalid_credentials');
        }

        $this->clearFailures($identifier);

        if (config('api_auth.require_otp_on_new_device', true) && !$this->knownDevice($user->id, $data['device_id'])) {
            return $this->startDeviceChallenge($user, $request);
        }

        return $this->sessionResponse($user, $request, 'api_login', 'Signed in with password');
    }

    /** Second step for an unseen device: confirm with the OTP that was just sent. */
    public function loginConfirm(Request $request): JsonResponse
    {
        $data = $this->validated($request, [
            'challenge' => 'required|string|max:100',
            'otp' => 'required|digits:6',
        ]);
        if ($data instanceof JsonResponse) {
            return $data;
        }

        $key = 'v2:chal:' . hash('sha256', $data['challenge']);
        $chal = Cache::get($key);
        if (!$chal) {
            return $this->fail('This sign-in request has expired. Please start again.', 410, 'challenge_expired');
        }

        if ($blocked = $this->blockedResponse($request, $chal['identifier'])) {
            return $blocked;
        }

        $result = $this->otp->verify($chal['identifier'], $data['otp'], 'device_confirm');
        if (!$result['ok']) {
            $this->recordFailure($chal['identifier']);
            return $this->fail($result['message'] ?? 'The code is incorrect.', 401, 'invalid_otp');
        }

        Cache::forget($key);
        $this->clearFailures($chal['identifier']);

        $user = User::find($chal['uid']);
        if (!$user || !$user->is_active) {
            return $this->fail(self::GENERIC_FAIL, 401, 'invalid_credentials');
        }

        $request->merge(['device_id' => $chal['device']['device_id'] ?? null]);

        return $this->sessionResponse($user, $request, 'api_login_device', 'New device confirmed with OTP', $chal['device']);
    }

    // ── OTP login ───────────────────────────────────────────────────────────

    public function otpSend(Request $request): JsonResponse
    {
        $data = $this->validated($request, ['identifier' => 'required|string|max:120']);
        if ($data instanceof JsonResponse) {
            return $data;
        }

        [$identifier, $type] = $this->normalize($data['identifier']);

        $limiter = 'v2:otp-send:' . sha1($identifier) . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($limiter, 3)) {
            return $this->fail('Too many requests. Please wait a minute.', 429, 'rate_limited', [
                'retry_after' => RateLimiter::availableIn($limiter),
            ]);
        }
        RateLimiter::hit($limiter, 60);

        $user = $this->findOwner($identifier, $type);
        if ($user && $user->is_active) {
            $this->otp->send($identifier, $type, 'login');
        }

        // Same answer either way — the app can't be used to find out who is registered.
        return response()->json([
            'success' => true,
            'message' => 'If this account exists, a code has been sent.',
            'data' => ['expires_in' => (int) config('otp.expiry_minutes', 10) * 60],
        ]);
    }

    public function otpVerify(Request $request): JsonResponse
    {
        $data = $this->validated($request, [
            'identifier' => 'required|string|max:120',
            'otp' => 'required|digits:6',
            'device_id' => 'required|string|max:100',
        ]);
        if ($data instanceof JsonResponse) {
            return $data;
        }

        [$identifier, $type] = $this->normalize($data['identifier']);

        if ($blocked = $this->blockedResponse($request, $identifier)) {
            return $blocked;
        }

        $user = $this->findOwner($identifier, $type);
        $result = $this->otp->verify($identifier, $data['otp'], 'login');

        if (!$user || !$user->is_active || !$result['ok']) {
            $this->recordFailure($identifier);
            $this->audit($user?->id, 'api_login_failed', 'OTP login failed (' . $request->ip() . ')');

            return $this->fail($user && $user->is_active ? ($result['message'] ?? self::GENERIC_FAIL) : self::GENERIC_FAIL, 401, 'invalid_otp');
        }

        $this->clearFailures($identifier);

        return $this->sessionResponse($user, $request, 'api_login_otp', 'Signed in with OTP');
    }

    // ── Registration (owners only, phone verified with an OTP) ──────────────

    public function registerStart(Request $request): JsonResponse
    {
        $data = $this->validated($request, [
            'name' => 'required|string|min:2|max:120',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:150',
            'password' => ['required', 'string', 'min:8', 'max:200', 'regex:/[A-Za-z]/', 'regex:/[0-9]/'],
            'device_id' => 'required|string|max:100',
        ]);
        if ($data instanceof JsonResponse) {
            return $data;
        }

        [$phone, $type] = $this->normalize($data['phone']);
        if ($type !== 'phone' || strlen($phone) !== 10) {
            return $this->fail('Enter a valid 10 digit mobile number.', 422, 'validation_failed');
        }

        $limiter = 'v2:register:' . sha1($phone) . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($limiter, 4)) {
            return $this->fail('Too many requests. Please wait a few minutes.', 429, 'rate_limited');
        }
        RateLimiter::hit($limiter, 300);

        $phoneTaken = User::whereRaw("RIGHT(REPLACE(REPLACE(REPLACE(phone, '+', ''), ' ', ''), '-', ''), 10) = ?", [$phone])->exists();
        if ($phoneTaken) {
            return $this->fail('An account with this mobile number already exists. Please sign in instead.', 409, 'phone_taken');
        }

        $email = !empty($data['email']) ? mb_strtolower(trim($data['email'])) : null;
        if ($email && User::whereRaw('LOWER(email) = ?', [$email])->exists()) {
            return $this->fail('An account with this email already exists. Please sign in instead.', 409, 'email_taken');
        }

        $sent = $this->otp->send($phone, 'phone', 'register');
        if (!$sent['ok'] && empty($sent['wait_seconds'])) {
            return $this->fail('We could not send the verification code. Please try again.', 502, 'otp_unavailable');
        }

        $challenge = Str::random(48);
        Cache::put('v2:reg:' . hash('sha256', $challenge), [
            'name' => trim($data['name']),
            'phone' => $phone,
            'email' => $email,
            'password' => Hash::make($data['password']),
            'device' => $this->deviceFrom($request),
        ], now()->addMinutes(15));

        return response()->json([
            'success' => true,
            'status' => 'otp_required',
            'data' => [
                'challenge' => $challenge,
                'sent_to' => $this->mask($phone, 'phone'),
                'expires_in' => 600,
            ],
        ], 202);
    }

    /**
     * Credit packages shown on the sign-up screen, before there is an account
     * (same list the website shows on /register-packages).
     */
    public function packages(): JsonResponse
    {
        $packages = \DB::table('credit_packages')->where('is_active', 1)->orderBy('credits')->get();
        return response()->json(['success' => true, 'data' => $packages]);
    }

    public function registerVerify(Request $request): JsonResponse
    {
        $data = $this->validated($request, [
            'challenge' => 'required|string|max:100',
            'otp' => 'required|digits:6',
        ]);
        if ($data instanceof JsonResponse) {
            return $data;
        }

        $key = 'v2:reg:' . hash('sha256', $data['challenge']);
        $pending = Cache::get($key);
        if (!$pending) {
            return $this->fail('This sign up request has expired. Please start again.', 410, 'challenge_expired');
        }

        if ($blocked = $this->blockedResponse($request, $pending['phone'])) {
            return $blocked;
        }

        $result = $this->otp->verify($pending['phone'], $data['otp'], 'register');
        if (!$result['ok']) {
            $this->recordFailure($pending['phone']);
            return $this->fail($result['message'] ?? 'The code is incorrect.', 401, 'invalid_otp');
        }
        $this->clearFailures($pending['phone']);
        Cache::forget($key);

        // Someone may have registered the same number in the meantime.
        $taken = User::whereRaw("RIGHT(REPLACE(REPLACE(REPLACE(phone, '+', ''), ' ', ''), '-', ''), 10) = ?", [$pending['phone']])->exists();
        if ($taken) {
            return $this->fail('An account with this mobile number already exists. Please sign in instead.', 409, 'phone_taken');
        }

        $user = User::create([
            'name' => $pending['name'],
            'phone' => $pending['phone'],
            // same placeholder the website uses when no email was given
            'email' => $pending['email'] ?: $pending['phone'] . '@temp.pizi.in',
            'password' => $pending['password'],
            'role' => 'owner',
            // free sign-up, like the website's /register-free; credits are bought later
            'signup_type' => 'free',
            'is_active' => true,
        ]);

        try {
            User::where('role', 'admin')->get()->each(
                fn ($admin) => $admin->notify(new \App\Notifications\NewOwnerSignup($user))
            );
        } catch (\Throwable $e) {
            \Log::warning('Owner signup notification failed: ' . $e->getMessage());
        }

        try {
            $wa = app(\App\Services\WhatsAppService::class);
            $wa->sendTemplate($user->phone, 'account_create', [$user->name, $user->phone]);
            $wa->sendTemplate($user->phone, 'pg_owner_welcome', []);
        } catch (\Throwable $e) {
            \Log::warning('Owner welcome WhatsApp failed: ' . $e->getMessage());
        }

        return $this->sessionResponse($user, $request, 'api_register', 'Account created from the owner app', $pending['device']);
    }

    // ── Session lifecycle ───────────────────────────────────────────────────

    public function refresh(Request $request): JsonResponse
    {
        $data = $this->validated($request, ['refresh_token' => 'required|string|max:200']);
        if ($data instanceof JsonResponse) {
            return $data;
        }

        $limiter = 'v2:refresh:' . $request->ip();
        if (RateLimiter::tooManyAttempts($limiter, 60)) {
            return $this->fail('Too many requests.', 429, 'rate_limited');
        }
        RateLimiter::hit($limiter, 60);

        $rotated = $this->tokens->rotate($data['refresh_token'], $request);
        if (!$rotated) {
            return $this->fail('Your session has ended. Please sign in again.', 401, 'session_ended');
        }

        $user = User::find($rotated['session']->user_id);
        if (!$user || !$user->is_active) {
            $this->tokens->revoke($rotated['session'], 'user_inactive');
            return $this->fail('Your session has ended. Please sign in again.', 401, 'session_ended');
        }

        return response()->json(['success' => true, 'data' => $rotated['tokens']]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->userResource($request->user)]);
    }

    public function logout(Request $request): JsonResponse
    {
        if ($session = $request->attributes->get('api_session')) {
            $this->tokens->revoke($session, 'logout');
            $this->audit($session->user_id, 'api_logout', 'Signed out');
        }

        return response()->json(['success' => true]);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $count = $this->tokens->revokeAll($request->user->id, 'logout_all');
        $this->audit($request->user->id, 'api_logout_all', "Signed out of {$count} session(s)");

        return response()->json(['success' => true, 'data' => ['revoked' => $count]]);
    }

    public function sessions(Request $request): JsonResponse
    {
        $current = $request->attributes->get('api_session');

        $rows = ApiSession::where('user_id', $request->user->id)
            ->whereNull('revoked_at')
            ->where('refresh_expires_at', '>', now())
            ->orderByDesc('last_used_at')
            ->get()
            ->map(fn (ApiSession $s) => [
                'id' => $s->uuid,
                'device_name' => $s->device_name ?: 'Unknown device',
                'platform' => $s->platform,
                'app_version' => $s->app_version,
                'ip' => $s->ip,
                'last_used_at' => optional($s->last_used_at)->toIso8601String(),
                'created_at' => $s->created_at->toIso8601String(),
                'current' => $current && $current->id === $s->id,
            ]);

        return response()->json(['success' => true, 'data' => $rows]);
    }

    public function revokeSession(Request $request, string $uuid): JsonResponse
    {
        $session = ApiSession::where('user_id', $request->user->id)->where('uuid', $uuid)->first();
        if (!$session) {
            return $this->fail('Session not found.', 404, 'not_found');
        }

        $this->tokens->revoke($session, 'revoked_by_user');
        $this->audit($request->user->id, 'api_session_revoked', 'Revoked a device session');

        return response()->json(['success' => true]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $data = $this->validated($request, [
            'current_password' => 'required|string|max:200',
            'new_password' => ['required', 'string', 'min:10', 'max:200', 'regex:/[a-z]/', 'regex:/[A-Z]/', 'regex:/[0-9]/'],
        ]);
        if ($data instanceof JsonResponse) {
            return $data;
        }

        $user = $request->user;

        $limiter = 'v2:pwchange:' . $user->id;
        if (RateLimiter::tooManyAttempts($limiter, 5)) {
            return $this->fail('Too many attempts. Please try again later.', 429, 'rate_limited');
        }
        RateLimiter::hit($limiter, 900);

        if (!Hash::check($data['current_password'], $user->password)) {
            return $this->fail('Your current password is incorrect.', 422, 'wrong_password');
        }

        $user->forceFill(['password' => Hash::make($data['new_password'])])->save();

        // Every other device has to sign in again with the new password.
        $current = $request->attributes->get('api_session');
        $revoked = $this->tokens->revokeAll($user->id, 'password_changed', $current?->id);
        $this->audit($user->id, 'api_password_changed', "Password changed; {$revoked} other session(s) signed out");

        return response()->json(['success' => true, 'data' => ['other_sessions_signed_out' => $revoked]]);
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function startDeviceChallenge(User $user, Request $request): JsonResponse
    {
        // Prefer the phone; fall back to e-mail.
        [$identifier, $type] = $user->phone
            ? $this->normalize($user->phone)
            : $this->normalize((string) $user->email);

        $limiter = 'v2:otp-send:' . sha1($identifier) . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($limiter, 3)) {
            return $this->fail('Too many requests. Please wait a minute.', 429, 'rate_limited');
        }
        RateLimiter::hit($limiter, 60);

        $sent = $this->otp->send($identifier, $type, 'device_confirm');
        if (!$sent['ok'] && empty($sent['wait_seconds'])) {
            return $this->fail('We could not send the verification code. Please try again.', 502, 'otp_unavailable');
        }

        $challenge = Str::random(48);
        Cache::put('v2:chal:' . hash('sha256', $challenge), [
            'uid' => $user->id,
            'identifier' => $identifier,
            'device' => $this->deviceFrom($request),
        ], now()->addMinutes(10));

        return response()->json([
            'success' => true,
            'status' => 'otp_required',
            'message' => 'Enter the code sent to confirm this device.',
            'data' => [
                'challenge' => $challenge,
                'sent_to' => $this->mask($identifier, $type),
                'expires_in' => 600,
            ],
        ], 202);
    }

    private function sessionResponse(User $user, Request $request, string $action, string $note, ?array $device = null): JsonResponse
    {
        $tokens = $this->tokens->issue($user, $device ?? $this->deviceFrom($request), $request);
        $this->audit($user->id, $action, $note . ' (' . $request->ip() . ')');

        return response()->json([
            'success' => true,
            'data' => array_merge($tokens, ['user' => $this->userResource($user)]),
        ]);
    }

    private function knownDevice(int $userId, string $deviceId): bool
    {
        return ApiSession::where('user_id', $userId)->where('device_id', $deviceId)->exists();
    }

    private function deviceFrom(Request $request): array
    {
        return [
            'device_id' => $request->input('device_id'),
            'device_name' => $request->input('device_name'),
            'platform' => $request->input('platform'),
            'app_version' => $request->input('app_version'),
        ];
    }

    /** @return array{0: string, 1: string} [normalised identifier, 'phone'|'email'] */
    private function normalize(string $raw): array
    {
        $raw = trim($raw);

        if (filter_var($raw, FILTER_VALIDATE_EMAIL)) {
            return [mb_strtolower($raw), 'email'];
        }

        $digits = preg_replace('/\D/', '', $raw);

        return [strlen($digits) > 10 ? substr($digits, -10) : $digits, 'phone'];
    }

    private function findOwner(string $identifier, string $type): ?User
    {
        $q = User::query()->whereIn('role', config('api_auth.allowed_roles'));

        if ($type === 'email') {
            return $q->whereRaw('LOWER(email) = ?', [$identifier])->first();
        }

        if (strlen($identifier) !== 10) {
            return null;
        }

        return $q->whereRaw("RIGHT(REPLACE(REPLACE(REPLACE(phone, '+', ''), ' ', ''), '-', ''), 10) = ?", [$identifier])->first();
    }

    private function blockedResponse(Request $request, string $identifier): ?JsonResponse
    {
        $ipLimiter = 'v2:auth-ip:' . $request->ip();
        if (RateLimiter::tooManyAttempts($ipLimiter, 30)) {
            return $this->fail('Too many attempts. Please try again shortly.', 429, 'rate_limited', [
                'retry_after' => RateLimiter::availableIn($ipLimiter),
            ]);
        }
        RateLimiter::hit($ipLimiter, 60);

        $failures = (int) Cache::get($this->failKey($identifier), 0);
        if ($failures >= (int) config('api_auth.max_failures')) {
            return $this->fail('This account is temporarily locked after too many attempts. Please try again later.', 423, 'locked', [
                'retry_after' => (int) config('api_auth.lockout_minutes') * 60,
            ]);
        }

        return null;
    }

    private function recordFailure(string $identifier): void
    {
        $key = $this->failKey($identifier);
        Cache::add($key, 0, now()->addMinutes((int) config('api_auth.lockout_minutes')));
        Cache::increment($key);
    }

    private function clearFailures(string $identifier): void
    {
        Cache::forget($this->failKey($identifier));
    }

    private function failKey(string $identifier): string
    {
        return 'v2:fail:' . sha1($identifier);
    }

    private function userResource(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'avatar' => $user->avatar ?? null,
            // what a PG Manager may open; owners get everything
            'permissions' => $user->role === 'pg_manager'
                ? (json_decode($user->permissions ?? '[]', true) ?: [])
                : null,
            'owner_id' => $user->role === 'pg_manager' ? $user->owner_id : null,
        ];
    }

    private function mask(string $value, string $type): string
    {
        if ($type === 'email') {
            [$name, $domain] = array_pad(explode('@', $value, 2), 2, '');

            return mb_substr($name, 0, 2) . '***@' . $domain;
        }

        return str_repeat('*', max(0, strlen($value) - 4)) . substr($value, -4);
    }

    private function audit(?int $userId, string $action, string $description): void
    {
        try {
            DB::table('user_activity_log')->insert([
                'user_id' => $userId,
                'action' => $action,
                'description' => mb_substr($description, 0, 250),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // an audit write must never block a login
        }
    }

    private function validated(Request $request, array $rules): array|JsonResponse
    {
        $v = Validator::make($request->all(), $rules);
        if ($v->fails()) {
            return $this->fail($v->errors()->first(), 422, 'validation_failed');
        }

        return $v->validated();
    }

    private function fail(string $message, int $status, string $code, array $extra = []): JsonResponse
    {
        return response()->json(array_merge([
            'success' => false,
            'message' => $message,
            'code' => $code,
        ], $extra), $status);
    }
}
