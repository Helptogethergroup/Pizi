<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function request()
    {
        return view('auth.forgot-password');
    }

    public function email(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.exists' => 'No account found with this email.',
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return back()->withErrors(['email' => 'Email not found']);
        }

        // Delete old tokens
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        // Create new token (plain text, not hashed)
        $token = Str::random(60);
        DB::table('password_reset_tokens')->insert([
            'email' => $request->email,
            'token' => $token,  // ✅ Store as plain text
            'created_at' => now(),
        ]);

        // Generate reset URL
        $resetUrl = route('password.reset', ['token' => $token, 'email' => $request->email]);
        
        try {
            Mail::raw("Click here to reset your password: " . $resetUrl, function ($message) use ($request) {
                $message->to($request->email)
                        ->subject('Password Reset Link - Pizi.in');
            });
        } catch (\Exception $e) {
            \Log::error('Password reset email failed: ' . $e->getMessage());
        }

        return back()->with('status', 'Password reset link sent to your email!');
    }

    public function show($token)
    {
        $passwordReset = DB::table('password_reset_tokens')
            ->where('token', $token)  // ✅ Direct comparison (no hashing)
            ->first();

        if (!$passwordReset) {
            return redirect()->route('password.request')->withErrors(['token' => 'Invalid or expired token']);
        }

        return view('auth.reset-password', [
            'token' => $token,
            'email' => $passwordReset->email,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        $passwordReset = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->token)  // ✅ Direct comparison
            ->first();

        if (!$passwordReset) {
            return back()->withErrors(['token' => 'Invalid or expired token']);
        }

        // Update user password
        User::where('email', $request->email)->update([
            'password' => Hash::make($request->password),
        ]);

        // Delete reset token
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return redirect()->route('login')->with('status', '✅ Password reset successful! Login with your new password.');
    }
}