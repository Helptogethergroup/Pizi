<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;

class PiziAuthenticate
{
    public function handle(Request $request, Closure $next)
    {
        $auth = $request->header('Authorization');
        if (!$auth || !str_starts_with($auth, 'Bearer ')) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $token = substr($auth, 7);
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
}