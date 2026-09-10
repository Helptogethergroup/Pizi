<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $accessToken;
    protected string $phoneNumberId;
    protected string $apiVersion;

    public function __construct()
    {
        $this->accessToken = (string) config('whatsapp.access_token');
        $this->phoneNumberId = (string) config('whatsapp.phone_number_id');
        $this->apiVersion = config('whatsapp.api_version', 'v20.0');
    }

    /**
     * Send a pre-approved WhatsApp template message.
     *
     * Safe to call even before WhatsApp is configured/approved — it
     * simply logs and returns ok=false instead of throwing, so it never
     * breaks bill creation / payment recording while waiting on Meta.
     *
     * @param string $phone       Any format — normalized internally
     * @param string $templateKey Key from config('whatsapp.templates')
     * @param array  $params      Values to fill {{1}}, {{2}}, ... in order
     */
    public function sendTemplate(string $phone, string $templateKey, array $params, ?string $buttonUrl = null): array
    {
        if (empty($this->accessToken) || empty($this->phoneNumberId)) {
            Log::info('WhatsApp not configured yet — skipping send', ['template' => $templateKey, 'phone' => $phone]);
            return ['ok' => false, 'message' => 'WhatsApp API not configured yet.'];
        }

        $templateConfig = config("whatsapp.templates.{$templateKey}");
        if (!$templateConfig) {
            return ['ok' => false, 'message' => "Unknown WhatsApp template key: {$templateKey}"];
        }

        // Support both old string format and new array format ['name'=>..., 'lang'=>...]
        $templateName = is_array($templateConfig) ? $templateConfig['name'] : $templateConfig;
        $langCode     = is_array($templateConfig) ? ($templateConfig['lang'] ?? 'en') : 'en';

        $phone = $this->normalizePhone($phone);
        $url = "https://graph.facebook.com/{$this->apiVersion}/{$this->phoneNumberId}/messages";

        // Build parameters — support named (associative) and positional (numeric) arrays
        // Named:      ['customer_name' => 'Rahul'] → sends parameter_name field (Meta named vars)
        // Positional: ['Rahul', '5000']            → plain text (numbered vars like {{1}})
        $parameters = [];
        foreach ($params as $key => $value) {
            if (is_string($key)) {
                // Named variable template
                $parameters[] = ['type' => 'text', 'parameter_name' => $key, 'text' => (string) $value];
            } else {
                // Numbered variable template
                $parameters[] = ['type' => 'text', 'text' => (string) $value];
            }
        }

        $components = empty($parameters) ? [] : [['type' => 'body', 'parameters' => $parameters]];

        // If template has a dynamic CTA button (Visit Website with {{1}}), send the URL suffix.
        // Meta rejects the whole message if the approved button is STATIC (no {{1}}) and we
        // still send a button parameter — so this is gated behind a config flag. Flip
        // WHATSAPP_BUTTON_IS_DYNAMIC=true in .env only after the template's button URL is
        // edited to Dynamic ({{1}}) in Meta Business Manager and re-approved.
        if ($buttonUrl !== null && config('whatsapp.button_is_dynamic')) {
            // Extract dynamic suffix — only the part after the base URL in the button template
            // Meta expects just the dynamic portion (bill number), not the full URL
            $components[] = [
                'type'       => 'button',
                'sub_type'   => 'url',
                'index'      => '0',
                'parameters' => [['type' => 'text', 'text' => $buttonUrl]],
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $phone,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $langCode],
                'components' => $components,
            ],
        ];

        Log::info('WhatsApp payload', [
            'template' => $templateName,
            'lang'     => $langCode,
            'phone'    => $phone,
            'params'   => $params,
        ]);

        try {
            $response = Http::withToken($this->accessToken)->timeout(20)->post($url, $payload);

            Log::info('WhatsApp send attempt', [
                'phone'    => $phone,
                'template' => $templateName,
                'lang'     => $langCode,
                'status'   => $response->status(),
                'body'     => $response->body(),
            ]);

            if ($response->successful()) {
                return ['ok' => true, 'response' => $response->json()];
            }

            return ['ok' => false, 'message' => $response->body()];

        } catch (\Exception $e) {
            Log::error('WhatsApp send exception', ['error' => $e->getMessage()]);
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Send a pre-approved template that has a DOCUMENT header (e.g. an invoice PDF).
     * The template itself is fixed/approved once in Meta Business Manager; only the
     * document link + filename + body text vary per send.
     *
     * @param string $documentLink A publicly fetchable URL (e.g. a signed route) to the PDF.
     */
    public function sendDocumentTemplate(
        string $phone,
        string $templateKey,
        string $documentLink,
        string $documentFilename,
        array $bodyParams = []
    ): array {
        if (empty($this->accessToken) || empty($this->phoneNumberId)) {
            Log::info('WhatsApp not configured yet — skipping document send', ['template' => $templateKey, 'phone' => $phone]);
            return ['ok' => false, 'message' => 'WhatsApp API not configured yet.'];
        }

        $templateConfig = config("whatsapp.templates.{$templateKey}");
        if (!$templateConfig) {
            return ['ok' => false, 'message' => "Unknown WhatsApp template key: {$templateKey}"];
        }

        $templateName = is_array($templateConfig) ? $templateConfig['name'] : $templateConfig;
        $langCode     = is_array($templateConfig) ? ($templateConfig['lang'] ?? 'en') : 'en';

        $phone = $this->normalizePhone($phone);
        $url = "https://graph.facebook.com/{$this->apiVersion}/{$this->phoneNumberId}/messages";

        $bodyParameters = array_map(fn ($v) => ['type' => 'text', 'text' => (string) $v], $bodyParams);

        $components = [
            [
                'type' => 'header',
                'parameters' => [[
                    'type' => 'document',
                    'document' => ['link' => $documentLink, 'filename' => $documentFilename],
                ]],
            ],
        ];

        if (!empty($bodyParameters)) {
            $components[] = ['type' => 'body', 'parameters' => $bodyParameters];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $phone,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $langCode],
                'components' => $components,
            ],
        ];

        Log::info('WhatsApp document payload', ['template' => $templateName, 'phone' => $phone, 'link' => $documentLink]);

        try {
            $response = Http::withToken($this->accessToken)->timeout(20)->post($url, $payload);

            Log::info('WhatsApp document send attempt', [
                'phone' => $phone,
                'template' => $templateName,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->successful()) {
                return ['ok' => true, 'response' => $response->json()];
            }

            return ['ok' => false, 'message' => $response->body()];
        } catch (\Exception $e) {
            Log::error('WhatsApp document send exception', ['error' => $e->getMessage()]);
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone);

        // Leading trunk-prefix 0 (e.g. "07983172396") — drop it before checking length
        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        if (strlen($digits) === 10) {
            $digits = '91' . $digits;
        }

        return $digits;
    }
}
