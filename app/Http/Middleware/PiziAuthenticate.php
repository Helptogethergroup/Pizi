<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ApiSession;
use App\Models\User;
use App\Services\ApiTokenService;

class PiziAuthenticate
{
    public function handle(Request $request, Closure $next)
    {
        $auth = $request->header('Authorization');
        if (!$auth || !str_starts_with($auth, 'Bearer ')) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $token = substr($auth, 7);

        // v2: session based tokens issued to the native owner app.
        if (str_starts_with($token, 'v2.')) {
            return $this->handleSessionToken($request, $next, $token);
        }

        $decoded = base64_decode($token, true);
        if (!$decoded) {
            return response()->json(['success' => false, 'message' => 'Invalid token'], 401);
        }

        $parts = explode('|', $decoded);
        if (count($parts) !== 3) {
            return response()->json(['success' => false, 'message' => 'Invalid token format'], 401);
        }

        [$userId, $hash, $expiry] = $parts;

        if ((int) $expiry < time()) {
            return response()->json(['success' => false, 'message' => 'Token expired'], 401);
        }

        $secret = config('app.key');
        $expected = hash('sha256', $userId . '|' . $expiry . '|' . $secret);
        if (!hash_equals($expected, $hash)) {
            return response()->json(['success' => false, 'message' => 'Invalid token signature'], 401);
        }

        $user = User::find($userId);
        if (!$user || (isset($user->is_active) && !$user->is_active)) {
            return response()->json(['success' => false, 'message' => 'User not found or disabled'], 401);
        }

        $request->user = $user;
        $request->setUserResolver(fn() => $user);

        return $next($request);
    }

    private function handleSessionToken(Request $request, Closure $next, string $token)
    {
        $tokens = app(ApiTokenService::class);

        $claims = $tokens->parseAccess($token);
        if (!$claims) {
            return response()->json(['success' => false, 'message' => 'Invalid token', 'code' => 'invalid_token'], 401);
        }

        // The app refreshes quietly when it sees this code.
        if ($tokens->accessIsExpired($claims)) {
            return response()->json(['success' => false, 'message' => 'Token expired', 'code' => 'token_expired'], 401);
        }

        $session = ApiSession::where('uuid', $claims['sid'])
            ->where('user_id', $claims['uid'])
            ->first();

        if (!$session || !$session->isActive()) {
            return response()->json(['success' => false, 'message' => 'Session ended', 'code' => 'session_ended'], 401);
        }

        $user = User::find($session->user_id);
        if (!$user || !$user->is_active) {
            return response()->json(['success' => false, 'message' => 'User not found or disabled', 'code' => 'session_ended'], 401);
        }

        // The app tells us its version on every request ("1.2.2+24"). Keep it fresh on the session, so an owner
        // who updates the app without signing in again stops being reminded to update. Written only when it changed.
        $reported = substr(trim((string) $request->header('X-App-Version', '')), 0, 20);
        $versionChanged = $reported !== '' && preg_match('/^\d+(\.\d+){0,3}(\+\d+)?$/', $reported) && $reported !== $session->app_version;

        // "last seen" without a write on every single request.
        if ($versionChanged || !$session->last_used_at || $session->last_used_at->lt(now()->subMinute())) {
            $changes = ['last_used_at' => now()];
            if ($versionChanged) {
                $changes['app_version'] = $reported;
            }
            $session->forceFill($changes)->saveQuietly();
        }

        $request->user = $user;
        $request->setUserResolver(fn() => $user);
        $request->attributes->set('api_session', $session);

        return $next($request);
    }
}
