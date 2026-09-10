<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\User;

class ApiAuthenticate
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized — no token provided'
            ], 401);
        }

        // Decode token (format: userId|hashedSecret)
        $userId = $this->validateToken($token);

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired token'
            ], 401);
        }

        $user = User::find($userId);

        if (!$user || !$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'User not found or inactive'
            ], 401);
        }

        // Attach user to request
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        return $next($request);
    }

    private function validateToken($token)
    {
        // Token format: base64(userId:secret:expiry)
        try {
            $decoded = base64_decode($token);
            if (!$decoded) return null;

            $parts = explode('|', $decoded);
            if (count($parts) !== 3) return null;

            [$userId, $secret, $expiry] = $parts;

            // Check expiry
            if ((int) $expiry < time()) return null;

            // Verify secret matches APP_KEY-based hash
            $expectedSecret = hash('sha256', $userId . config('app.key') . $expiry);
            if (!hash_equals($expectedSecret, $secret)) return null;

            return (int) $userId;
        } catch (\Exception $e) {
            return null;
        }
    }
}
