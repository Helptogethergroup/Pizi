<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Actually send the OTP via SMS (TextGuru) or Email — this was previously
     * a TODO / not implemented at all. Uses the same TextGuru config as the
     * website's OTP system.
     */
    private function sendOtpMessage(string $identifier, string $otp): bool
    {
        $isEmail = (bool) filter_var($identifier, FILTER_VALIDATE_EMAIL);

        if ($isEmail) {
            try {
                Mail::raw("Your OTP for Pizi is: {$otp}\n\nValid for 10 minutes. Do not share with anyone.", function ($msg) use ($identifier) {
                    $msg->to($identifier)->subject('Your Pizi OTP');
                });
                return true;
            } catch (\Exception $e) {
                Log::error('API Email OTP failed', ['email' => $identifier, 'error' => $e->getMessage()]);
                return false;
            }
        }

        // Phone — same TextGuru setup used by the website
        $phone = preg_replace('/[^0-9]/', '', $identifier);
        if (strlen($phone) === 10) {
            $phone = '91' . $phone;
        }

        $username = env('TEXTGURU_USERNAME');
        $password = env('TEXTGURU_PASSWORD');
        $senderId = env('TEXTGURU_SENDER_ID', 'PIZIND');
        $templateId = env('TEXTGURU_TEMPLATE_ID');

        if (empty($username) || empty($password) || empty($templateId)) {
            Log::warning('API OTP: SMS service not configured');
            return false;
        }

        $message = "Your OTP for Pizi login is {$otp}. Valid for 10 minutes. Do not share with anyone. Developed By Help Together Group.";

        $url = 'https://www.textguru.in/api/v22.0/?' . http_build_query([
            'username' => $username,
            'password' => $password,
            'source' => $senderId,
            'sender' => $senderId,
            'senderid' => $senderId,
            'header' => $senderId,
            'dmobile' => $phone,
            'dlttempid' => $templateId,
            'message' => $message,
        ]);

        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $body = trim(curl_exec($ch) ?: '');
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            Log::info('API TextGuru SMS response', ['phone' => $phone, 'http_status' => $httpCode, 'body' => $body]);

            $bodyLower = strtolower($body);
            return $httpCode === 200 && (
                str_contains($bodyLower, 'msgid') ||
                str_contains($bodyLower, 'submitted') ||
                str_contains($bodyLower, 'success')
            );
        } catch (\Exception $e) {
            Log::error('API TextGuru SMS exception', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Generate custom token: base64(userId|sha256|expiry)
     */
    private function generateToken(int $userId): string
    {
        $expiry = now()->addDays(7)->timestamp;
        $secret = config('app.key');
        $hash = hash('sha256', $userId . '|' . $expiry . '|' . $secret);
        return base64_encode($userId . '|' . $hash . '|' . $expiry);
    }

public function login(Request $request)
{
    try {
        $request->validate([
            'identifier' => 'required|string',
        ]);

        $identifier = trim($request->identifier);
        $isEmail    = filter_var($identifier, FILTER_VALIDATE_EMAIL);

        $user = $isEmail
            ? User::where('email', $identifier)->first()
            : User::where('phone', $identifier)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $otpService     = app(\App\Services\OtpService::class);
        $identifierType = $isEmail ? 'email' : 'phone';
        $result         = $otpService->send($identifier, $identifierType, 'login');

        if (!$result['ok']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Could not send OTP.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP sent',
            'data'    => [
                'identifier'     => $identifier,
                'role'           => $user->role,
                'otp_expires_in' => 600,
                'debug_otp'      => $result['debug_otp'] ?? null,
            ],
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Login failed: ' . $e->getMessage(),
        ], 500);
    }
}


 public function register(Request $request)
{
    try {
        // Validate
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|digits:10|unique:users,phone',
            'password' => 'required|string|min:6',
            'role' => 'required|in:guest,owner,user,tenant,telecaller'
        ]);

        // Create user
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'] === 'guest' ? 'user' : $validated['role'],
            'is_active' => 1,
            'email_verified_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully',
            'data' => [
                'user' => $user
            ]
        ], 201);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => $e->errors()
        ], 422);

    } catch (\Exception $e) {
        \Log::error('Registration error: ' . $e->getMessage());
        
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}

    public function otpSend(Request $request)
    {
        $request->validate(['identifier' => 'required|string']);

        $identifier = trim($request->identifier);
        $otp = (string) rand(100000, 999999);

        // Cache OTP for 10 mins — used when the identifier doesn't belong to an
        // existing user yet (registration flow), checked in otpVerify() below.
        Cache::put('otp:' . $identifier, $otp, now()->addMinutes(10));

        $sent = $this->sendOtpMessage($identifier, $otp);

        if (!$sent) {
            return response()->json([
                'success' => false,
                'message' => 'Could not send OTP. Please try again in a moment.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP sent.',
            'data' => config('app.debug') ? ['otp' => $otp] : null,
        ]);
    }

public function otpVerify(Request $request)
{
    try {
        $request->validate([
            'identifier' => 'required|string',
            'otp'        => 'required|digits:6',
        ]);

        $identifier = trim($request->identifier);
        $isEmail    = filter_var($identifier, FILTER_VALIDATE_EMAIL);

        $otpService = app(\App\Services\OtpService::class);
        $result     = $otpService->verify($identifier, $request->otp, 'login');

        if (!$result['ok']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 401);
        }

        $user = $isEmail
            ? User::where('email', $identifier)->first()
            : User::where('phone', $identifier)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $token = $this->generateToken($user->id);

        return response()->json([
            'success' => true,
            'data'    => [
                'user'  => $this->userResource($user),
                'token' => $token,
            ],
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Verification failed: ' . $e->getMessage(),
        ], 500);
    }
}

    public function otpResend(Request $request)
    {
        return $this->otpSend($request);
    }

    public function registerComplete(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:6',
            'role' => 'required|in:guest,owner,user',
            'otp_token' => 'required|string',
        ]);

        $identifier = Cache::get('otp_token:' . $request->otp_token);
        if (!$identifier) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired session. Please retry OTP.',
            ], 422);
        }

        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);
        $data = [
            'name' => $request->name,
            'password' => Hash::make($request->password),
            'role' => $request->role === 'guest' ? 'user' : $request->role,
            'is_active' => 1,
            'email_verified_at' => now(),
        ];

        if ($isEmail) {
            $data['email'] = $identifier;
            $data['phone'] = $request->phone ?? null;
        } else {
            $data['phone'] = $identifier;
            $data['email'] = $request->email ?? null;
        }

        $user = User::create($data);
        Cache::forget('otp_token:' . $request->otp_token);

        $token = $this->generateToken($user->id);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $this->userResource($user),
                'token' => $token,
            ],
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user;
        return response()->json([
            'success' => true,
            'data' => $this->userResource($user),
        ]);
    }

    public function logout(Request $request)
    {
        return response()->json(['success' => true]);
    }

    private function userResource($user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'is_active' => (int) ($user->is_active ?? 1),
            'avatar' => $user->avatar ?? null,
        ];
    }
    
    public function loginWithPassword(Request $request)
{
    try {
        $request->validate([
            'identifier' => 'required|string',
            'password'   => 'required|string',
        ]);

        $identifier = trim($request->identifier);
        $isEmail    = filter_var($identifier, FILTER_VALIDATE_EMAIL);

        $user = $isEmail
            ? User::where('email', $identifier)->first()
            : User::where('phone', $identifier)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid password.',
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Account is inactive.',
            ], 403);
        }

        $token = $this->generateToken($user->id);

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data'    => [
                'user'  => $this->userResource($user),
                'token' => $token,
            ],
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Login failed: ' . $e->getMessage(),
        ], 500);
    }
}
}