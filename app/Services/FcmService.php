<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends push notifications through Firebase Cloud Messaging (HTTP v1 API).
 *
 * Needs a Firebase service-account JSON (services.fcm.credentials). Until
 * that file is in place every method here quietly returns 0 — callers never
 * need to check configuration, and a push problem can never break the
 * request that triggered it.
 */
class FcmService
{
    private ?array $credentials = null;
    private bool $credentialsLoaded = false;

    public function isConfigured(): bool
    {
        return $this->credentials() !== null;
    }

    /**
     * Pushes to every device registered for the user.
     * Returns how many devices accepted the message.
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): int
    {
        if (!$this->isConfigured()) {
            return 0;
        }

        // Sessions that expired mean the app auto-logged-out without telling us.
        $user->deviceTokens()->where('session_expires_at', '<', now())->delete();

        $devices = $user->deviceTokens()->get();
        if ($devices->isEmpty()) {
            return 0;
        }

        $accessToken = $this->accessToken();
        if (!$accessToken) {
            return 0;
        }

        $projectId = $this->credentials()['project_id'];
        $data = collect($data)->filter(fn ($v) => $v !== null)->map(fn ($v) => (string) $v)->all();
        $sent = 0;

        foreach ($devices as $device) {
            try {
                $response = Http::withToken($accessToken)
                    ->timeout(5)
                    ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                        'message' => [
                            'token' => $device->token,
                            'notification' => ['title' => $title, 'body' => $body],
                            'data' => (object) $data,
                            'android' => ['priority' => 'HIGH'],
                        ],
                    ]);

                if ($response->successful()) {
                    $device->forceFill(['last_used_at' => now()])->save();
                    $sent++;
                    continue;
                }

                // The app was uninstalled / token rotated — stop pushing to it.
                if (in_array($response->json('error.status'), ['UNREGISTERED', 'NOT_FOUND'], true)) {
                    $device->delete();
                    continue;
                }

                Log::warning('FCM push rejected', ['status' => $response->status(), 'body' => $response->body()]);
            } catch (\Throwable $e) {
                Log::warning('FCM push failed: ' . $e->getMessage());
            }
        }

        return $sent;
    }

    private function credentials(): ?array
    {
        if ($this->credentialsLoaded) {
            return $this->credentials;
        }
        $this->credentialsLoaded = true;

        $path = config('services.fcm.credentials');
        if (!$path || !is_readable($path)) {
            return null;
        }

        $json = json_decode((string) file_get_contents($path), true);
        if (!is_array($json) || empty($json['project_id']) || empty($json['client_email']) || empty($json['private_key'])) {
            Log::warning('FCM credentials file is missing project_id / client_email / private_key');
            return null;
        }

        return $this->credentials = $json;
    }

    /** Google OAuth2 access token (service-account JWT flow), cached ~50 min. */
    private function accessToken(): ?string
    {
        $cred = $this->credentials();
        if (!$cred) {
            return null;
        }

        return Cache::remember('fcm_access_token', 3000, function () use ($cred) {
            $now = time();
            $header = $this->base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $claims = $this->base64Url(json_encode([
                'iss' => $cred['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ]));

            $signature = '';
            if (!openssl_sign("{$header}.{$claims}", $signature, $cred['private_key'], OPENSSL_ALGO_SHA256)) {
                Log::warning('FCM: could not sign the OAuth JWT (check private_key in the credentials file)');
                return null;
            }

            $response = Http::asForm()->timeout(5)->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => "{$header}.{$claims}." . $this->base64Url($signature),
            ]);

            if (!$response->successful()) {
                Log::warning('FCM: OAuth token request failed', ['status' => $response->status(), 'body' => $response->body()]);
                return null;
            }

            return $response->json('access_token');
        });
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
