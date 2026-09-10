<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Setu Aadhaar eSign Service — Pizi.in Integration
 *
 * Auth method : x-client-id / x-client-secret / x-product-instance-id headers
 * Upload      : multipart/form-data  (NOT base64 JSON)
 * Position    : named string e.g. 'bottom-right'  (NOT x/y pixel object)
 */
class SetuEsignService
{
    protected string $baseUrl;
    protected string $clientId;
    protected string $clientSecret;
    protected string $productInstanceId;

    public function __construct()
    {
        $this->baseUrl           = rtrim(config('services.setu.base_url'), '/');
        $this->clientId          = (string) config('services.setu.client_id');
        $this->clientSecret      = (string) config('services.setu.client_secret');
        $this->productInstanceId = (string) config('services.setu.product_instance_id');

        if (! $this->clientId || ! $this->clientSecret || ! $this->productInstanceId) {
            throw new RuntimeException(
                'Missing Setu credentials. Set SETU_CLIENT_ID, SETU_CLIENT_SECRET and SETU_ESIGN_PRODUCT_INSTANCE_ID in .env'
            );
        }
    }

    // ------------------------------------------------------------------ //
    //  Internal HTTP client — header-based auth (no OAuth token needed)
    // ------------------------------------------------------------------ //

    protected function client()
    {
        return Http::withHeaders([
            'x-client-id'          => $this->clientId,
            'x-client-secret'      => $this->clientSecret,
            'x-product-instance-id' => $this->productInstanceId,
            'Accept'               => 'application/json',
        ])->baseUrl($this->baseUrl);
    }

    // ------------------------------------------------------------------ //
    //  1. Upload document
    // ------------------------------------------------------------------ //

    /**
     * Upload a PDF from a file path.
     * Returns Setu response — use ['id'] as documentId for next step.
     */
    public function uploadDocument(string $filePath, string $displayName): array
    {
        if (! file_exists($filePath)) {
            throw new RuntimeException("PDF file not found: {$filePath}");
        }

        $bytes    = file_get_contents($filePath);
        $fileName = basename($filePath);

        return $this->uploadDocumentBytes($bytes, $fileName, $displayName);
    }

    /**
     * Upload raw PDF bytes (used when generating PDF in memory via DomPDF).
     *
     * @param  string  $bytes       Raw PDF content (e.g. from $pdf->output())
     * @param  string  $fileName    Filename Setu will see, e.g. "agreement_42.pdf"
     * @param  string  $displayName Human-readable name stored on Setu dashboard
     * @return array                Setu response — contains 'id' key
     */
    public function uploadDocumentBytes(string $bytes, string $fileName, string $displayName): array
    {
        Log::info('[SetuEsign] uploadDocument', ['fileName' => $fileName]);

        $response = $this->client()
            ->attach('document', $bytes, $fileName, ['Content-Type' => 'application/pdf'])
            ->post('/api/documents', [
                'name' => $displayName,
            ]);

        return $this->handle($response, 'uploadDocument');
    }

    // ------------------------------------------------------------------ //
    //  2. Create signature request
    // ------------------------------------------------------------------ //

    /**
     * Create a signature request for a single signer.
     *
     * @param  string  $documentId   Comes from uploadDocument()['id']
     * @param  array   $signer       Must contain at minimum:
     *                               [
     *                                 'identifier'  => '9876543210',  // mobile / email
     *                                 'displayName' => 'Rahul Kumar',
     *                                 'signature'   => [
     *                                     'onPages'  => ['1'],        // page numbers as strings
     *                                     'position' => 'bottom-left', // named position (see below)
     *                                     'height'   => 60,
     *                                     'width'    => 180,
     *                                 ],
     *                               ]
     *                               Optional: 'birthYear' => 1995
     *
     * Valid position values:
     *   top-left | top-center | top-right
     *   middle-left | middle-center | middle-right
     *   bottom-left | bottom-center | bottom-right
     *
     * @param  string  $redirectUrl  Publicly reachable URL Setu redirects back to
     * @param  string|null $configId Optional Setu configId
     * @return array                 Setu response — contains 'id' and 'signers'
     *                               Use $response['signers'][0]['url'] to redirect user
     */
    public function createSignatureRequest(
        string $documentId,
        array $signer,
        string $redirectUrl,
        ?string $configId = null
    ): array {
        Log::info('[SetuEsign] createSignatureRequest', [
            'documentId'  => $documentId,
            'identifier'  => $signer['identifier'] ?? null,
            'redirectUrl' => $redirectUrl,
        ]);

        $payload = [
            'documentId'  => $documentId,
            'signers'     => [$signer],
            'redirectUrl' => $redirectUrl,
        ];

        if ($configId) {
            $payload['configId'] = $configId;
        }

        $response = $this->client()->post('/api/signature', $payload);

        return $this->handle($response, 'createSignatureRequest');
    }

    // ------------------------------------------------------------------ //
    //  3. Get signature status
    // ------------------------------------------------------------------ //

    /**
     * Fetch status of a signature request.
     * Useful in callback to confirm actual status from Setu.
     * Look for $response['status'] === 'sign_complete'
     */
    public function getSignatureStatus(string $signatureRequestId): array
    {
        $response = $this->client()->get("/api/signature/{$signatureRequestId}");

        return $this->handle($response, 'getSignatureStatus');
    }

    // ------------------------------------------------------------------ //
    //  4. Get signed document download URL
    // ------------------------------------------------------------------ //

    /**
     * Get a time-limited download URL for the signed document.
     * Returns ['downloadUrl' => 'https://...']
     * Only works when status === 'sign_complete'
     */
    public function getSignedDocumentDownload(string $signatureRequestId): array
    {
        $response = $this->client()->get("/api/signature/{$signatureRequestId}/download/");

        return $this->handle($response, 'getSignedDocumentDownload');
    }

    // ------------------------------------------------------------------ //
    //  5. Optional: Delete a signature request
    // ------------------------------------------------------------------ //

    public function deleteSignatureRequest(string $signatureRequestId): array
    {
        $response = $this->client()->post("/api/signature/{$signatureRequestId}/delete/");

        if ($response->status() === 204) {
            return ['success' => true];
        }

        return $this->handle($response, 'deleteSignatureRequest');
    }

    // ------------------------------------------------------------------ //
    //  Internal error handler
    // ------------------------------------------------------------------ //

    protected function handle($response, string $caller = ''): array
    {
        if ($response->failed()) {
            $body   = $response->json();
            $detail = $body['error']['detail']
                ?? $body['error']['code']
                ?? $body['message']
                ?? $response->reason();

            Log::error("[SetuEsign] {$caller} failed", [
                'status' => $response->status(),
                'body'   => $body,
            ]);

            throw new RuntimeException("Setu API error [{$caller}] ({$response->status()}): {$detail}");
        }

        return $response->json() ?? [];
    }
}