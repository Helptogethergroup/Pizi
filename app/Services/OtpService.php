<?php

namespace App\Services;

use App\Models\OtpVerification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    public function send(string $identifier, string $type = 'phone', string $purpose = 'login'): array
    {
        $lastOtp = OtpVerification::for($identifier, $purpose)->latest()->first();
        $resendWait = (int) config('otp.resend_seconds', 60);

        if ($lastOtp && $lastOtp->created_at->addSeconds($resendWait)->isFuture()) {
            $waitTime = (int) now()->diffInSeconds($lastOtp->created_at->addSeconds($resendWait), false);
            return [
                'ok' => false,
                'message' => "Please wait {$waitTime} seconds before requesting another OTP.",
                'wait_seconds' => $waitTime,
            ];
        }

        $otp = $this->generateOtp();

        OtpVerification::for($identifier, $purpose)
            ->where('verified', false)
            ->update(['verified' => true]);

        $expiryMinutes = (int) config('otp.expiry_minutes', 10);

        OtpVerification::create([
            'identifier' => $identifier,
            'identifier_type' => $type,
            'otp' => $otp,
            'purpose' => $purpose,
            'expires_at' => now()->addMinutes($expiryMinutes),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        if ($type === 'phone') {
            $result = $this->sendSms($identifier, $otp);
        } else {
            $result = $this->sendEmail($identifier, $otp, $purpose);
        }

        if (!$result['ok']) {
            Log::warning('OTP send failed', ['identifier' => $identifier, 'error' => $result['message'] ?? 'unknown']);
        }

        $baseMsg = $result['ok']
            ? "OTP sent to {$this->maskIdentifier($identifier, $type)}. Valid for {$expiryMinutes} minutes."
            : ($result['message'] ?? 'Failed to send OTP. Try again.');

        return [
            'ok' => $result['ok'],
            'message' => $baseMsg,
            'expires_in' => $expiryMinutes * 60,
            'debug_otp' => config('app.debug') ? $otp : null,
        ];
    }

    public function verify(string $identifier, string $otp, string $purpose = 'login'): array
    {
        $record = OtpVerification::for($identifier, $purpose)
            ->where('verified', false)
            ->latest()
            ->first();

        if (!$record) {
            return ['ok' => false, 'message' => 'No active OTP found. Please request a new one.'];
        }

        if ($record->isExpired()) {
            return ['ok' => false, 'message' => 'OTP has expired. Please request a new one.'];
        }

        if ($record->isMaxAttempts()) {
            return ['ok' => false, 'message' => 'Too many failed attempts. Please request a new OTP.'];
        }

        $record->increment('attempts');

        if ($record->otp !== $otp) {
            $remaining = (int) config('otp.max_attempts', 3) - $record->attempts;
            return [
                'ok' => false,
                'message' => "Invalid OTP. {$remaining} attempts remaining.",
                'attempts_left' => $remaining,
            ];
        }

        $record->update([
            'verified' => true,
            'verified_at' => now(),
        ]);

        return ['ok' => true, 'message' => 'OTP verified successfully.'];
    }

    /**
     * Send OTP via TextGuru SMS API.
     *
     * Uses Laravel's Http client (same proven pattern as the working
     * Rail ORH Portal integration) instead of raw curl, and reads
     * credentials via config() so values remain available even after
     * `php artisan config:cache` is run (env() returns null for
     * anything not read inside a config/*.php file once cached).
     */
    private function sendSms(string $phone, string $otp): array
    {
        // Normalize phone (remove +91, spaces, etc.)
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone) === 10) {
            $phone = '91' . $phone;
        }

        $username   = config('textguru.username');
        $password   = config('textguru.password');
        $senderId   = config('textguru.sender_id');
        $templateId = config('textguru.template_id');
        $apiUrl     = config('textguru.api_url', 'https://www.textguru.in/api/v22.0/');

        if (empty($username) || empty($password) || empty($templateId)) {
            return ['ok' => false, 'message' => 'SMS service not configured.'];
        }

        $message = "Your OTP for Pizi login is {$otp}. Valid for 10 minutes. Do not share with anyone. Developed By Help Together Group."; 
        

        Log::info('TextGuru API call', [
            'phone' => $phone,
            'sender' => $senderId,
            'template' => $templateId,
        ]);

        // Local dev: SMS send mat karo — debug_otp response mein dikhta hai
        if (app()->environment('local')) {
            Log::info('LOCAL: SMS skipped (debug_otp available in response)', ['phone' => $phone, 'otp' => '(check debug_otp in API response)']);
            return ['ok' => true, 'response' => 'local-skip'];
        }

        try {
            $response = Http::asForm()->timeout(20)->post($apiUrl, [
                'username'  => $username,
                'password'  => $password,
                'source'    => $senderId,
                'dmobile'   => $phone,
                'dlttempid' => $templateId,
                'message'   => $message,
            ]);

            $body = trim($response->body());
            $httpCode = $response->status();

            Log::info('TextGuru SMS response', [
                'phone' => $phone,
                'http_status' => $httpCode,
                'body' => $body,
            ]);

            $bodyLower = strtolower($body);

            // Success indicators (TextGuru returns message ID or "submitted" on success)
            if ($response->successful() && (
                str_contains($bodyLower, 'msgid') ||
                str_contains($bodyLower, 'submitted') ||
                str_contains($bodyLower, 'success') ||
                str_contains($bodyLower, 'sent') ||
                str_contains($bodyLower, 'mobilecount') ||
                preg_match('/^[A-Za-z0-9_-]{8,}$/', $body)
            )) {
                return ['ok' => true, 'response' => $body];
            }

            return [
                'ok' => false,
                'message' => 'SMS: ' . substr($body, 0, 200),
                'response' => $body,
            ];

        } catch (\Exception $e) {
            Log::error('TextGuru SMS exception', ['error' => $e->getMessage()]);
            return ['ok' => false, 'message' => 'SMS service error: ' . $e->getMessage()];
        }
    }

    private function generateOtp(): string
    {
        return str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function maskIdentifier(string $identifier, string $type): string
    {
        if ($type === 'phone') {
            $digits = preg_replace('/[^0-9]/', '', $identifier);
            $digits = substr($digits, -10);
            return substr($digits, 0, 2) . str_repeat('X', 6) . substr($digits, -2);
        }

        [$user, $domain] = explode('@', $identifier);
        $masked = substr($user, 0, 2) . str_repeat('*', max(1, strlen($user) - 2));
        return $masked . '@' . $domain;
    }

    private function sendEmail(string $identifier, string $otp, string $purpose = 'login'): array
    {
        $subject = 'Your Pizi OTP';
        $message = "Your OTP for Pizi {$purpose} is: {$otp}\n\nValid for 10 minutes. Do not share with anyone.";

        try {
            Mail::raw($message, function ($msg) use ($identifier, $subject) {
                $msg->to($identifier)
                    ->subject($subject);
            });

            Log::info('Email OTP sent', ['email' => $identifier, 'purpose' => $purpose]);
            return ['ok' => true, 'message' => 'Email sent successfully'];

        } catch (\Exception $e) {
            Log::error('Email OTP failed', ['email' => $identifier, 'error' => $e->getMessage()]);
            return ['ok' => false, 'message' => 'Failed to send email: ' . $e->getMessage()];
        }
    }
}
