<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    protected \App\Services\OtpService $otpService;

    public function __construct(\App\Services\OtpService $otpService)
    {
        $this->otpService = $otpService;
    }
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

    if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            \DB::table('user_activity_log')->insert([
                'user_id' => Auth::id(),
                'action' => 'login',
                'description' => 'Logged in via password (' . $request->ip() . ')',
                'created_at' => now(),
            ]);

            return $this->redirectByRole(Auth::user());
        }

        return back()->withErrors(['email' => 'Invalid credentials.'])->withInput();
    }

  public function showRegister()
{
    $packages = \App\Models\CreditPackage::where('is_active', true)
        ->orderBy('display_order')
        ->orderBy('price_inr')
        ->get();
    
    return view('auth.register', compact('packages'));
}
    // Self-registration is OWNER-only now — tenants are added by their owner
    // (with Aadhaar-based KYC done on their behalf), never sign up themselves.
    public function register(Request $request)
{
    if ($request->isJson()) {
        try {
            $data = $request->validate([
                'name' => 'required|string|max:120',
                'email' => 'required|email|unique:users,email',
                'phone' => 'required|string|max:15',
                'password' => ['required', 'confirmed', Password::min(6)],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first(),
            ], 422);
        }
    } else {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:15',
            'password' => ['required', 'confirmed', Password::min(6)],
        ]);
    }

    // Block duplicate accounts on the same phone number — compare the last
    // 10 digits so "+91 98765 43210", "9876543210" etc. all match.
    if ($this->phoneAlreadyRegistered($data['phone'])) {
        $message = 'An account with this phone number already exists. Please log in instead.';
        if ($request->isJson()) {
            return response()->json(['success' => false, 'message' => $message], 422);
        }
        return back()->withErrors(['phone' => $message])->withInput();
    }

    $user = User::create([
        'name' => $data['name'],
        'email' => $data['email'],
        'phone' => $data['phone'],
        'password' => Hash::make($data['password']),
        'role' => 'owner',
        'signup_type' => $request->boolean('is_free') ? 'free' : 'paid',
    ]);

    try {
        User::where('role', 'admin')->get()->each(
            fn ($admin) => $admin->notify(new \App\Notifications\NewOwnerSignup($user))
        );
    } catch (\Exception $e) {
        \Log::warning('Owner signup notification failed: ' . $e->getMessage());
    }

    // WhatsApp welcome message — free registration flow ke liye bhi
    app(\App\Services\WhatsAppService::class)->sendTemplate(
        $user->phone,
        'account_create',
        [$user->name, $user->phone]
    );

    // pg_owenr_welcome template body has 0 variables on Meta — send no params.
    try {
        app(\App\Services\WhatsAppService::class)->sendTemplate(
            $user->phone,
            'pg_owner_welcome',
            []
        );
    } catch (\Exception $e) {
        \Log::warning('Owner welcome WhatsApp failed: ' . $e->getMessage());
    }

    Auth::login($user);

    session()->flash('success', '🎉 Welcome to Pizi! Add your first property to get started.');

    if ($request->isJson()) {
        return response()->json(['success' => true]);
    }

    return $this->redirectByRole($user);
}

   public function logout(Request $request)
    {
        if (Auth::check()) {
            \DB::table('user_activity_log')->insert([
                'user_id' => Auth::id(),
                'action' => 'logout',
                'description' => 'Logged out (' . $request->ip() . ')',
                'created_at' => now(),
            ]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    protected function redirectByRole($user)
    {
        return match ($user->role) {
              'admin' => redirect()->route('admin.dashboard'),
            'owner' => redirect()->route('owner.dashboard'),
            'pg_manager' => redirect()->route('owner.dashboard'),
            'telecaller' => redirect()->route('telecaller.dashboard'),
            'field_executive' => redirect()->route('field.dashboard'),
            'seo_manager' => redirect()->route('seo.dashboard'),
            'tenant' => $user->journey_stage >= 5 
              ? redirect()->route('tenant.dashboard') 
              : redirect()->route('tenant.onboarding'),
            default => redirect()->route('home'),
        };
    }
    
    /**
     * ===== Inline phone verification for registration forms =====
     */

    public function registerSendOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
        ]);

        $digits = preg_replace('/[^0-9]/', '', $request->phone);
        if (strlen($digits) < 10) {
            return response()->json(['success' => false, 'message' => 'Enter a valid 10-digit mobile number.']);
        }
        $phone = substr($digits, -10);

        if (User::where('phone', $phone)->exists()) {
            return response()->json(['success' => false, 'message' => 'An account with this number already exists. Please login.']);
        }

        $result = $this->otpService->send($phone, 'phone', 'register');

        $msg = $result['message'];
        if (!empty($result['debug_otp']) && env('APP_DEBUG')) {
            $msg .= " [TEST OTP: {$result['debug_otp']}]";
        }

        return response()->json(['success' => $result['ok'], 'message' => $msg]);
    }

    public function registerVerifyOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'otp' => 'required|digits:6',
        ]);

        $digits = preg_replace('/[^0-9]/', '', $request->phone);
        $phone = substr($digits, -10);

        $result = $this->otpService->verify($phone, $request->otp, 'register');

        if ($result['ok']) {
            session(['phone_verified_' . $phone => true]);
        }

        return response()->json(['success' => $result['ok'], 'message' => $result['message']]);
    }

    /**
     * Checks the last 10 digits of the phone against existing users, so
     * "+91 98765 43210", "9876543210", "098765 43210" etc. all collide as
     * the same number regardless of how each signup form typed it.
     */
    private function phoneAlreadyRegistered(string $phone): bool
    {
        $digits = preg_replace('/[^0-9]/', '', $phone);
        $last10 = substr($digits, -10);

        if (strlen($last10) < 10) {
            return false;
        }

        return User::whereRaw(
            'RIGHT(REPLACE(REPLACE(phone, "+91", ""), " ", ""), 10) = ?',
            [$last10]
        )->exists();
    }
}