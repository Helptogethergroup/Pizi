<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class OtpController extends Controller
{
    protected OtpService $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Show OTP login page
     */
    public function showLoginForm()
    {
        return view('auth.otp-login');
    }

    /**
     * Send OTP (login or register flow)
     */
    public function sendOtp(Request $request)
    {
       
        $data = $request->validate([
            'identifier' => 'required|string',
            'purpose' => 'nullable|in:login,register',
        ]);

        $purpose = $data['purpose'] ?? 'login';
        $identifier = trim($data['identifier']);

        // Detect type
        $type = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        if ($type === 'phone') {
            $digits = preg_replace('/[^0-9]/', '', $identifier);
            if (strlen($digits) < 10) {
                return back()->withErrors(['identifier' => 'Enter a valid 10-digit mobile number.']);
            }
            $identifier = substr($digits, -10);
        }

        // For LOGIN: user must exist
        if ($purpose === 'login') {
            $userExists = User::where($type === 'phone' ? 'phone' : 'email', $identifier)->exists();
            if (!$userExists) {
                return back()->withErrors(['identifier' => 'No account found. Please register first.']);
            }
        }

        // For REGISTER: user must NOT exist
        if ($purpose === 'register') {
            $userExists = User::where($type === 'phone' ? 'phone' : 'email', $identifier)->exists();
            if ($userExists) {
                return back()->withErrors(['identifier' => 'Account already exists. Please login.']);
            }
        }

        $result = $this->otpService->send($identifier, $type, $purpose);

        if (!$result['ok']) {
            return back()->withErrors(['identifier' => $result['message']]);
        }

        // Store in session for verification step
        session([
            'otp_identifier' => $identifier,
            'otp_type' => $type,
            'otp_purpose' => $purpose,
        ]);

        $msg = $result['message'];
        if (!empty($result['debug_otp']) && env('APP_DEBUG')) {
            $msg .= " [TEST OTP: {$result['debug_otp']}]";
        }

        return redirect()->route('otp.verify.show')->with('success', $msg);
    }

    /**
     * Show OTP verify form
     */
    public function showVerifyForm()
    {
        
        if (!session('otp_identifier')) {
            return redirect()->route('login')->withErrors(['identifier' => 'Session expired. Try again.']);
        }

        return view('auth.otp-verify', [
            'identifier' => session('otp_identifier'),
            'type' => session('otp_type'),
            'purpose' => session('otp_purpose'),
        ]);
    }

    /**
     * Verify OTP and login/register
     */
    public function verifyOtp(Request $request)
    {
        $data = $request->validate([
            'otp' => 'required|digits:6',
        ]);

        $identifier = session('otp_identifier');
        $type = session('otp_type');
        $purpose = session('otp_purpose');

        if (!$identifier) {
            return redirect()->route('login')->withErrors(['otp' => 'Session expired.']);
        }

        $result = $this->otpService->verify($identifier, $data['otp'], $purpose);

        if (!$result['ok']) {
            return back()->withErrors(['otp' => $result['message']]);
        }

        // OTP Verified — proceed with login or register
        if ($purpose === 'login') {
            $user = User::where($type === 'phone' ? 'phone' : 'email', $identifier)->first();

            if (!$user || !$user->is_active) {
                return redirect()->route('login')->withErrors(['otp' => 'Account inactive. Contact admin.']);
            }

          Auth::login($user, true);
            session()->forget(['otp_identifier', 'otp_type', 'otp_purpose']);

            \DB::table('user_activity_log')->insert([
                'user_id' => $user->id,
                'action' => 'login',
                'description' => 'Logged in via OTP (' . $request->ip() . ')',
                'created_at' => now(),
            ]);

            // Purane unlinked leads ko is user se link karo (phone/email match)
            $this->linkLeadsToUser($user);

            return $this->redirectByRole($user);
        }

        // Register flow — show registration form to complete profile
        if ($purpose === 'register') {
            session(['otp_verified_identifier' => $identifier, 'otp_verified_type' => $type]);
            session()->forget(['otp_identifier', 'otp_type', 'otp_purpose']);
            return redirect()->route('register.complete');
        }

        return redirect()->route('home');
    }

    /**
     * Resend OTP
     */
    public function resendOtp(Request $request)
    {
        $identifier = session('otp_identifier');
        $type = session('otp_type');
        $purpose = session('otp_purpose');

        if (!$identifier) {
            return redirect()->route('login')->withErrors(['otp' => 'Session expired.']);
        }

        $result = $this->otpService->send($identifier, $type, $purpose);

        if (!$result['ok']) {
            return back()->withErrors(['otp' => $result['message']]);
        }

        $debugOtp = isset($result['debug_otp']) ? $result['debug_otp'] : null;
         $msg = $result['message'];
        if ($debugOtp && env('APP_DEBUG')) {
    $msg .= " [TEST OTP: $debugOtp]";
     }
       return back()->with('success', $msg)->with('otp_sent', true);
    }

    /**
     * Show register complete form (after OTP verified for new user)
     */
    public function showRegisterComplete()
    {
        if (!session('otp_verified_identifier')) {
            return redirect()->route('register');
        }

        return view('auth.register-complete', [
            'identifier' => session('otp_verified_identifier'),
            'type' => session('otp_verified_type'),
        ]);
    }

    /**
     * Comp  */
    public function completeRegister(Request $request)
   {
        $data = $request->validate([
            'name' => 'required|string|max:200',
            'password' => 'required|string|min:6',
            'role' => 'nullable|in:guest,owner',
        ]);

        $identifier = session('otp_verified_identifier');
        $type = session('otp_verified_type');

        if (!$identifier) {
            return redirect()->route('register');
        }

        $userData = [
            'name' => $data['name'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'] ?? 'guest',
            'is_active' => true,
        ];

        if ($type === 'phone') {
            $userData['phone'] = $identifier;
            $userData['email'] = $identifier . '@temp.pizi.in'; // placeholder
        } else {
            $userData['email'] = $identifier;
            $userData['phone'] = $request->input('phone', '0000000000');
        }

        // Block duplicate accounts on the same phone number — compare the
        // last 10 digits so "+91 98765 43210", "9876543210" etc. all match.
        $phoneDigits = preg_replace('/[^0-9]/', '', $userData['phone']);
        $last10 = substr($phoneDigits, -10);
        if (strlen($last10) === 10) {
            $exists = User::whereRaw(
                'RIGHT(REPLACE(REPLACE(phone, "+91", ""), " ", ""), 10) = ?',
                [$last10]
            )->exists();

            if ($exists) {
                session()->forget(['otp_verified_identifier', 'otp_verified_type']);
                return redirect()->route('login')
                    ->withErrors(['phone' => 'An account with this phone number already exists. Please log in instead.']);
            }
        }

        $user = User::create($userData);

        if ($user->role === 'owner') {
            try {
                User::where('role', 'admin')->get()->each(
                    fn ($admin) => $admin->notify(new \App\Notifications\NewOwnerSignup($user))
                );
            } catch (\Exception $e) {
                \Log::warning('Owner signup notification failed: ' . $e->getMessage());
            }
        }

        Auth::login($user, true);
        session()->forget(['otp_verified_identifier', 'otp_verified_type']);

        \DB::table('user_activity_log')->insert([
            'user_id' => $user->id,
            'action' => 'login',
            'description' => 'Account created & first login via OTP (' . $request->ip() . ')',
            'created_at' => now(),
        ]);

        // WhatsApp welcome message — Owner aur Tenant/Guest dono ke liye
        if ($user->phone) {
            app(\App\Services\WhatsAppService::class)->sendTemplate(
                $user->phone,
                'account_create',
                [$user->name, $user->phone]
            );

            // Owner-specific welcome — separate from the generic account_create above.
            // pg_owenr_welcome template body has 0 variables on Meta — send no params.
            if ($user->role === 'owner') {
                try {
                    app(\App\Services\WhatsAppService::class)->sendTemplate(
                        $user->phone,
                        'pg_owner_welcome',
                        []
                    );
                } catch (\Exception $e) {
                    \Log::warning('Owner welcome WhatsApp failed: ' . $e->getMessage());
                }
            }
        }

        // Register ke baad bhi purane leads link karo
        $this->linkLeadsToUser($user);

        return $this->redirectByRole($user);
    }

   private function redirectByRole($user)
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
     * Purane leads (jo user_id NULL hain) ko phone/email se is user se jodo
     */
    private function linkLeadsToUser($user)
    {
        if (!$user) return;

        $last10 = $user->phone
            ? substr(preg_replace('/[^0-9]/', '', $user->phone), -10)
            : null;

        \DB::table('leads')
            ->whereNull('user_id')
            ->where(function ($q) use ($user, $last10) {
                if ($user->email) {
                    $q->where('email', $user->email);
                }
                if ($last10) {
                    $q->orWhereRaw('RIGHT(REPLACE(REPLACE(phone, "+91", ""), " ", ""), 10) = ?', [$last10]);
                }
            })
            ->update(['user_id' => $user->id, 'updated_at' => now()]);
    }
}   