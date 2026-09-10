<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureTenantJourneyComplete
{
   public function handle(Request $request, Closure $next)
{
    $user = Auth::user();

    if (!$user || $user->role !== 'tenant') {
        return $next($request);
    }

    $currentRoute = $request->route()?->getName();

    // Onboarding/profile/token/kyc pages hamesha allow — yahan se redirect NAHI
    $allowedRoutes = [
        'tenant.onboarding',
        'tenant.onboarding.update',
        'tenant.kyc.upload',
        'tenant.kyc.aadhaar',
        'tenant.kyc.aadhaar.sendotp',
        'tenant.kyc.aadhaar.verifyotp',
        'tenant.kyc.aadhaar.skiptesting',
        'tenant.agreement.sign',
        'tenant.agreement.sign.submit',
        'tenant.token.pay',
        'tenant.token.verify',
        'tenant.token.cash',
        'tenant.profile',
        'tenant.profile.update',
        'logout',
    ];

    if (in_array($currentRoute, $allowedRoutes)) {
        return $next($request);
    }

    // Baaki tenant pages: stage < 5 hai to onboarding bhejo
    if ((int) $user->journey_stage < 5) {
        return redirect()->route('tenant.onboarding');
    }

    return $next($request);
}

}