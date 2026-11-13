<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Setu DigiLocker: the tenant signs in to DigiLocker on Setu's page and agrees to share their
 * Aadhaar, and we read it back. There is no captcha or OTP in our app; the whole sign-in happens on
 * the DigiLocker page the person is sent to.
 *
 *   1. createRequest($redirectUrl)  -> id + the page (url) the tenant opens
 *   2. status($id)                  -> unauthenticated | authenticated | revoked
 *   3. aadhaar($id)                 -> name, date of birth, gender, masked number, address
 *
 * Keys: SETU_KYC_CLIENT_ID, SETU_KYC_CLIENT_SECRET, SETU_KYC_PRODUCT_INSTANCE_ID (Setu Bridge > KYC),
 * and SETU_KYC_BASE_URL (sandbox by default). Nothing personal is ever written to the log here.
 */
class SetuDigilockerService
{
    protected string $baseUrl;
    protected string $clientId;
    protected string $clientSecret;
    protected string $productInstanceId;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.setu_kyc.base_url', 'https://dg-sandbox.setu.co'), '/');
        $this->clientId = (string) config('services.setu_kyc.client_id');
        $this->clientSecret = (string) config('services.setu_kyc.client_secret');
        $this->productInstanceId = (string) config('services.setu_kyc.product_instance_id');
    }

    public function isConfigured(): bool
    {
        return $this->clientId !== '' && $this->clientSecret !== '' && $this->productInstanceId !== '';
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

    /** A message that is safe to show the tenant for one of Setu's error codes. */
    protected function friendly(?string $code, ?string $fallback = null): string
    {
        return match ($code) {
            'aadhaar_not_linked' => 'Your Aadhaar is not linked with your DigiLocker account. Open DigiLocker, link your Aadhaar there, and try again.',
            'user_consent_not_given', 'consent_not_given' => 'You did not agree to share your Aadhaar. Please try again and tap Allow.',
            'aadhaar_not_available' => 'Your Aadhaar is not available in DigiLocker right now. Please try again later.',
            'user_not_authenticated' => 'The DigiLocker sign-in was not completed. Please try again.',
            'token_revoked', 'request_not_found' => 'This check has expired. Please start again.',
            'upstream_server_error' => 'DigiLocker is not responding right now. Please try again in a few minutes.',
            default => $fallback ?: 'Aadhaar check could not be completed. Please try again.',
        };
    }

    /** @return array{success: bool, id?: string, url?: string, valid_upto?: ?string, message?: string} */
    public function createRequest(string $redirectUrl): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'Aadhaar check is not switched on yet.'];
        }
        try {
            $res = $this->client()->post('/api/digilocker/', ['redirectUrl' => $redirectUrl]);
            $body = $res->json() ?: [];
            Log::info('Setu DigiLocker createRequest', ['status' => $res->status()]);

            if ($res->successful() && !empty($body['id']) && !empty($body['url'])) {
                return ['success' => true, 'id' => $body['id'], 'url' => $body['url'], 'valid_upto' => $body['validUpto'] ?? null];
            }

            return ['success' => false, 'message' => $this->friendly($body['error']['code'] ?? null)];
        } catch (\Throwable $e) {
            Log::error('Setu DigiLocker createRequest failed: ' . $e->getMessage());

            return ['success' => false, 'message' => 'Aadhaar check is not available right now. Please try again later.'];
        }
    }

    /** @return array{success: bool, status?: string, message?: string} */
    public function status(string $id): array
    {
        try {
            $res = $this->client()->get('/api/digilocker/' . rawurlencode($id) . '/status');
            $body = $res->json() ?: [];
            if ($res->successful()) {
                return ['success' => true, 'status' => (string) ($body['status'] ?? 'unauthenticated')];
            }

            return ['success' => false, 'message' => $this->friendly($body['error']['code'] ?? null)];
        } catch (\Throwable $e) {
            Log::error('Setu DigiLocker status failed: ' . $e->getMessage());

            return ['success' => false, 'message' => 'Aadhaar check is not available right now. Please try again later.'];
        }
    }

    /**
     * The Aadhaar details, once the tenant has agreed on DigiLocker's page.
     *
     * @return array{success: bool, data?: array, message?: string}
     */
    public function aadhaar(string $id): array
    {
        try {
            $res = $this->client()->get('/api/digilocker/' . rawurlencode($id) . '/aadhaar');
            $body = $res->json() ?: [];
            // log the outcome only, never the person's details
            Log::info('Setu DigiLocker aadhaar', ['status' => $res->status()]);

            $a = $body['aadhaar'] ?? null;
            if ($res->successful() && is_array($a) && !empty($a['name'])) {
                $addr = is_array($a['address'] ?? null) ? $a['address'] : [];

                return ['success' => true, 'data' => [
                    'name' => $a['name'],
                    'masked_aadhaar' => $a['maskedNumber'] ?? null,
                    'dob' => $this->date($a['dateOfBirth'] ?? null),
                    'gender' => match (strtoupper((string) ($a['gender'] ?? ''))) {
                        'M' => 'male',
                        'F' => 'female',
                        default => null,
                    },
                    'address' => collect([$addr['house'] ?? null, $addr['street'] ?? null, $addr['landmark'] ?? null, $addr['locality'] ?? null,
                        $addr['vtc'] ?? null, $addr['subDistrict'] ?? null, $addr['district'] ?? null, $addr['state'] ?? null, $addr['pin'] ?? null])
                        ->filter()->implode(', '),
                    'city' => $addr['district'] ?? null,
                    'state' => $addr['state'] ?? null,
                    'pincode' => $addr['pin'] ?? null,
                ]];
            }

            return ['success' => false, 'message' => $this->friendly($body['error']['code'] ?? null)];
        } catch (\Throwable $e) {
            Log::error('Setu DigiLocker aadhaar failed: ' . $e->getMessage());

            return ['success' => false, 'message' => 'Aadhaar check is not available right now. Please try again later.'];
        }
    }

    /** Aadhaar gives DD-MM-YYYY; the database wants YYYY-MM-DD. */
    protected function date(?string $raw): ?string
    {
        if (!$raw) {
            return null;
        }
        foreach (['d-m-Y', 'Y-m-d', 'd/m/Y'] as $fmt) {
            $d = \DateTime::createFromFormat($fmt, $raw);
            if ($d && $d->format($fmt) === $raw) {
                return $d->format('Y-m-d');
            }
        }

        return null;
    }
}
