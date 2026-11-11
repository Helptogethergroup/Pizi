<?php

namespace App\Http\Controllers\Api;

/**
 * Sign-in for the renter app (/api/v2/renter/auth/*): the people who live in a PG (role "tenant")
 * and the people still looking for one (role "guest"). Everything else (password rules, OTP,
 * rotating refresh tokens, lockout, device sessions) is the same code the owner app uses.
 *
 * An owner, manager or admin account cannot sign in here, and a renter cannot sign in to the owner
 * app: each app only knows its own roles.
 */
class RenterAuthController extends AuthV2Controller
{
    protected function allowedRoles(): array
    {
        return config('api_auth.renter_roles');
    }

    /** Somebody who signs up in the renter app is looking for a PG until an owner adds them as a tenant. */
    protected function signupRole(): string
    {
        return 'guest';
    }
}
