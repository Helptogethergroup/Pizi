<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

/**
 * "Continue with Google" — AUTOFILL ONLY.
 *
 * This does not create an account and does not log anyone in. It just
 * fetches the visitor's name/email from Google and hands it back to
 * whichever form sent them here, so they don't have to type it manually.
 * The visitor still fills in the rest (password, phone, plan, etc.) and
 * submits the real form themselves — login, register, and register-free
 * all keep their existing normal flow untouched.
 */
class GoogleAuthController extends AuthController
{
    public function redirect(Request $request)
    {
        // Remember which page to bounce back to with the prefilled details.
        session(['google_prefill_return' => url()->previous() ?: route('login')]);

        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        $return = session()->pull('google_prefill_return', route('login'));

        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Exception $e) {
            Log::warning('Google OAuth prefill failed: ' . $e->getMessage());
            return redirect($return)->withErrors(['email' => 'Could not fetch details from Google. Please fill the form manually.']);
        }

        return redirect($return)->with('google_prefill', [
            'name'  => $googleUser->getName(),
            'email' => $googleUser->getEmail(),
        ]);
    }
}
