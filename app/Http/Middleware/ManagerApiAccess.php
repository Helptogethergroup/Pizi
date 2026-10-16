<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Default-deny gate for PG Managers on the owner API.
 *
 * An owner passes straight through. A manager may only call the routes listed
 * here, only when the owner granted the matching feature, and only while both
 * the manager and the owner account are active. Everything else (wallet
 * purchase, expenses, deposits, creating or deleting rooms and tenants,
 * managing other managers...) is refused, so an endpoint added later is closed
 * to managers until someone adds it on purpose.
 */
class ManagerApiAccess
{
    /** route => feature the owner must have granted ('' = any manager). */
    private const ALLOWED = [
        'GET owner/dashboard' => '',
        'GET owner/profile' => '',

        // the list only names the manager's own properties; tenants, rooms and rent need it
        'GET owner/properties' => '',
        'GET owner/properties/{id}' => 'properties',

        'GET owner/leads' => 'leads',
        'GET owner/leads/filters' => 'leads',
        'POST owner/leads/{id}/unlock' => 'leads',
        'PATCH owner/leads/{id}/status' => 'leads',
        'POST owner/leads/{id}/remark' => 'leads',
        'GET owner/leads/{id}/timeline' => 'leads',
        'POST owner/leads/{id}/report-junk' => 'leads',
        'GET owner/wallet' => 'leads',

        'GET owner/rent' => 'rent',
        'GET owner/rent/{id}' => 'rent',
        'POST owner/rent/{id}/payment' => 'rent',

        'GET owner/tenants' => 'tenants',
        'GET owner/tenants/{id}' => 'tenants',

        'GET owner/rooms' => 'rooms',
        'GET owner/rooms/{id}' => 'rooms',

        'GET owner/complaints' => 'complaints',
        'GET owner/complaints/{id}' => 'complaints',
        'POST owner/complaints/{id}/status' => 'complaints',
    ];

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'pg_manager') {
            return $next($request);
        }

        $owner = $user->owner_id ? \App\Models\User::find($user->owner_id) : null;
        if (!$user->is_active || !$owner || !$owner->is_active) {
            return response()->json(['success' => false, 'message' => 'This manager account is switched off. Please contact your owner.'], 403);
        }

        $uri = preg_replace('#^api/#', '', $request->route()?->uri() ?? '');
        $key = $request->method() . ' ' . $uri;

        if (!array_key_exists($key, self::ALLOWED)) {
            return response()->json(['success' => false, 'message' => 'This is only available to the owner.'], 403);
        }

        $feature = self::ALLOWED[$key];
        if ($feature !== '' && !$user->hasFeature($feature)) {
            return response()->json(['success' => false, 'message' => 'Your owner has not given you access to this.'], 403);
        }

        return $next($request);
    }
}
