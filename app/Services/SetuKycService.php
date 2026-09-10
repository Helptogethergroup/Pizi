<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Setu "eKYC Setu" (Aadhaar Paperless Offline eKYC) integration.
 *
 * Real Setu flow (confirmed against Setu's own product demo — captcha,
 * then OTP + a 4-digit share code the caller invents to "lock" the
 * returned Aadhaar data):
 *
 *   1. createRequest()                                → id + captcha image
 *   2. verifyAadhaar($id, $aadhaarNumber, $captcha)    → sends OTP to the
 *      Aadhaar-linked mobile once the Aadhaar number + captcha are valid
 *   3. verifyOtp($id, $otp, $shareCode)                → returns the full
 *      KYC payload (name, dob, gender, address, photo), decrypted using
 *      the share code
 *
 * ⚠️ Separate Setu product from eSign — needs its own Product Instance ID
 * (SETU_KYC_PRODUCT_INSTANCE_ID in .env), activated from Setu's dashboard
 * under KYC → eKYC Setu (for Aadhaar). Endpoint paths below are Setu's
 * documented Aadhaar OKYC paths — confirm against the live API docs once
 * the product instance ID is issued, since Setu occasionally versions
 * these per account.
 */
class SetuKycService
{
    protected string $baseUrl;
    protected string $clientId;
    protected string $clientSecret;
    protected string $productInstanceId;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.setu.base_url', 'https://dg-sandbox.setu.co'), '/');
        $this->clientId = (string) config('services.setu.client_id');
        $this->clientSecret = (string) config('services.setu.client_secret');
        $this->productInstanceId = (string) config('services.setu.kyc_product_instance_id');
    }

    public function isConfigured(): bool
    {
        return !empty($this->clientId) && !empty($this->clientSecret) && !empty($this->productInstanceId);
    }

    protected function client()
    {
        return Http::withHeaders([
            'x-client-id' => $this->clientId,
            'x-client-secret' => $this->clientSecret,
            'x-product-instance-id' => $this->productInstanceId,
            'Accept' => 'application/json',
        ])->timeout(30)->baseUrl($this->baseUrl);
    }

    /**
     * Step 1 — start a request, get back a captcha image to show the owner.
     */
    public function createRequest(): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Aadhaar KYC not configured yet — set SETU_KYC_PRODUCT_INSTANCE_ID in .env.',
            ];
        }

        try {
            $response = $this->client()->post('/api/kyc/aadhaar-okyc/');
            $body = $response->json();

            Log::info('Setu Aadhaar createRequest', ['status' => $response->status(), 'body' => array_diff_key($body ?? [], ['captcha' => 1])]);

            if ($response->successful() && !empty($body['id'])) {
                return [
                    'success' => true,
                    'id' => $body['id'],
                    'captcha_image' => $body['captcha'] ?? $body['captcha_image'] ?? null,
                    'valid_upto' => $body['validUpto'] ?? null,
                ];
            }

            return [
                'success' => false,
                'message' => $body['error']['detail'] ?? $body['message'] ?? 'Could not start Aadhaar verification.',
            ];
        } catch (\Exception $e) {
            Log::error('Setu Aadhaar createRequest exception: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Aadhaar verification service unavailable right now.'];
        }
    }

    /**
     * Step 2 — submit Aadhaar number + captcha text. On success, Setu
     * sends an OTP to the Aadhaar-linked mobile number.
     */
    public function verifyAadhaar(string $requestId, string $aadhaarNumber, string $captchaCode): array
    {
        try {
            $response = $this->client()->post("/api/kyc/aadhaar-okyc/{$requestId}/verify-captcha", [
                'aadhaarNumber' => $aadhaarNumber,
                'captcha' => $captchaCode,
            ]);

            $body = $response->json();
            Log::info('Setu Aadhaar verifyAadhaar', ['status' => $response->status(), 'body' => $body]);

            if ($response->successful()) {
                return ['success' => true, 'message' => 'OTP sent to the Aadhaar-linked mobile number.'];
            }

            return [
                'success' => false,
                'message' => $body['error']['detail'] ?? $body['message'] ?? 'Aadhaar/captcha did not match. Try again.',
            ];
        } catch (\Exception $e) {
            Log::error('Setu Aadhaar verifyAadhaar exception: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Aadhaar verification service unavailable right now.'];
        }
    }

    /**
     * Step 3 — submit the OTP + a share code (any 4 digits the owner
     * picks, used by Setu to encrypt/lock the returned Aadhaar data).
     * Returns the full KYC payload on success.
     */
    public function verifyOtp(string $requestId, string $otp, string $shareCode): array
    {
        try {
            $response = $this->client()->post("/api/kyc/aadhaar-okyc/{$requestId}/verify-otp", [
                'otp' => $otp,
                'shareCode' => $shareCode,
            ]);

            $body = $response->json();
            Log::info('Setu Aadhaar verifyOtp', ['status' => $response->status(), 'body' => array_diff_key($body ?? [], ['photo' => 1])]);

            if ($response->successful() && !empty($body['name'])) {
                return [
                    'success' => true,
                    'name' => $body['name'] ?? null,
                    'masked_aadhaar' => $body['maskedAadhaarNumber'] ?? $body['aadhaarNumber'] ?? null,
                    'data' => [
                        'name' => $body['name'] ?? null,
                        'dob' => $body['dob'] ?? null,
                        'gender' => match (strtoupper($body['gender'] ?? '')) {
                            'M' => 'male',
                            'F' => 'female',
                            default => null,
                        },
                        'address' => $this->formatAddress($body['address'] ?? []),
                        'city' => $body['address']['dist'] ?? null,
                        'state' => $body['address']['state'] ?? null,
                        'pincode' => $body['address']['pincode'] ?? null,
                        'photo_base64' => $body['photo'] ?? null,
                        'aadhaar_last4' => substr(preg_replace('/\D/', '', $body['maskedAadhaarNumber'] ?? $body['aadhaarNumber'] ?? ''), -4),
                    ],
                ];
            }

            return [
                'success' => false,
                'message' => $body['error']['detail'] ?? $body['message'] ?? 'OTP verification failed. Please try again.',
            ];
        } catch (\Exception $e) {
            Log::error('Setu Aadhaar verifyOtp exception: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Aadhaar verification service unavailable right now.'];
        }
    }

    private function formatAddress(array $addr): string
    {
        return collect([
            $addr['house'] ?? null,
            $addr['street'] ?? null,
            $addr['landmark'] ?? null,
            $addr['loc'] ?? null,
            $addr['vtc'] ?? null,
            $addr['dist'] ?? null,
            $addr['state'] ?? null,
            $addr['pincode'] ?? null,
        ])->filter()->implode(', ');
    }
}
