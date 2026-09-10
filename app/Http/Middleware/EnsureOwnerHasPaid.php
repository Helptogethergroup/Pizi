<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Payment;

class EnsureOwnerHasPaid
{
   public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if ($user && $user->role === 'owner' && $user->signup_type !== 'free') {

            // Legacy account — is fix se pehle registered ho chuka tha, hamesha allow karo
            $isLegacyAccount = $user->created_at && $user->created_at->lt(\Carbon\Carbon::parse('2026-07-19 00:00:00'));

            // Already koi property list ki hui hai — matlab genuine active owner hai
            $hasProperty = \App\Models\Property::where('owner_id', $user->id)->exists();

            $hasPaid = Payment::where('user_id', $user->id)
                ->where('status', 'paid')
                ->exists();

            if (!$hasPaid && !$hasProperty && !$isLegacyAccount) {
                return redirect('/register-packages')
                    ->with('error', '⚠️ Dashboard access ke liye pehle payment complete karo.');
            }
        }

        return $next($request);
    }
}