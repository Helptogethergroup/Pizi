<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckFeature
{
    public function handle(Request $request, Closure $next, string $feature)
    {
        $user = $request->user();

        if (!$user) {
            abort(403);
        }

        // Owners aur admins ke liye always allow
        if ($user->role !== 'pg_manager') {
            return $next($request);
        }

        // PG Manager ke liye feature check
        if (!$user->hasFeature($feature)) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Access denied.'], 403);
            }
            return redirect()->route('owner.dashboard')
                ->with('error', "⛔ You don't have access to this feature. Contact your owner.");
        }

        return $next($request);
    }
}