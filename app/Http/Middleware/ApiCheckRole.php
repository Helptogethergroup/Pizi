<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiCheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        if (!in_array($user->role, $roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden — insufficient role. Required: ' . implode(', ', $roles)
            ], 403);
        }

        return $next($request);
    }
}
