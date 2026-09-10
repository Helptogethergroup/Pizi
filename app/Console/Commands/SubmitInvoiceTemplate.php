<?php

namespace App\Console\Commands;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Submits the 'invoice_ready' WhatsApp template — the one with a DOCUMENT
 * header used to auto-send invoice PDFs. Document-header templates need a
 * sample file uploaded through Meta's resumable Upload API first (to get a
 * "header_handle"), which is a different flow than the plain body-only
 * templates in SubmitWhatsAppTemplates, hence its own command.
 *
 * Usage:
 *   php artisan whatsapp:submit-invoice-template            (preview only)
 *   php artisan whatsapp:submit-invoice-template --send     (actually submit)
 */
class SubmitInvoiceTemplate extends Command
{
    protected $signature = 'whatsapp:submit-invoice-template {--send : Actually submit to Meta}';

    protected $description = 'Submit the invoice_ready (document-header) WhatsApp template to Meta for review';

    public function handle(): int
    {
        $wabaId = config('whatsapp.waba_id');
        $token = config('whatsapp.access_token');
        $appId = env('META_APP_ID');
        $version = config('whatsapp.api_version', 'v20.0');

        if (!$wabaId || !$token) {
            $this->error('WHATSAPP_WABA_ID / WHATSAPP_ACCESS_TOKEN missing from .env.');
            return self::FAILURE;
        }
        if (!$appId) {
            $this->error('META_APP_ID missing from .env — needed for the sample-file upload step.');
            return self::FAILURE;
        }

        $bodyText = "Hi {{1}}, your Pizi invoice {{2}} for ₹{{3}} is attached.\n\nThank you for choosing Pizi!";
        $bodyExample = ['Rajesh', 'PIZI-2026-000001', '999.00'];

        if (!$this->option('send')) {
            $this->info('Preview mode — nothing submitted. Use --send to actually submit.');
            $this->line('Header: DOCUMENT (sample invoice PDF)');
            $this->line('Body: ' . str_replace("\n", ' ⏎ ', $bodyText));
            return self::SUCCESS;
        }

        // 1. Render a sample invoice PDF for Meta's reviewers to see.
        $sample = (object) [
            'invoice_number' => 'PIZI-2026-000001',
            'created_at' => now(),
            'type' => 'auto',
            'title' => 'Sample Credit Package',
            'base_amount' => 846.61,
            'gst_rate' => 18.00,
            'gst_amount' => 152.39,
            'total_amount' => 999.00,
            'notes' => null,
            'owner' => (object) ['name' => 'Rajesh Sharma', 'phone' => '919999999999', 'email' => 'sample@pizi.in'],
        ];
        $pdfBytes = Pdf::loadView('invoices.pdf', ['invoice' => $sample])->output();

        // 2a. Start a resumable upload session.
        $this->info('Uploading sample PDF to Meta...');
        $sessionResp = Http::withToken($token)
            ->post("https://graph.facebook.com/{$version}/{$appId}/uploads", [
                'file_length' => strlen($pdfBytes),
                'file_type' => 'application/pdf',
            ]);

        if (!$sessionResp->successful()) {
            $this->error('Failed to start upload session: ' . $sessionResp->body());
            return self::FAILURE;
        }

        $uploadSessionId = $sessionResp->json('id');

        // 2b. Upload the actual bytes.
        $uploadResp = Http::withHeaders([
            'Authorization' => "OAuth {$token}",
            'file_offset' => '0',
        ])->withBody($pdfBytes, 'application/pdf')
            ->post("https://graph.facebook.com/{$version}/{$uploadSessionId}");

        if (!$uploadResp->successful()) {
            $this->error('Failed to upload sample file: ' . $uploadResp->body());
            return self::FAILURE;
        }

        $handle = $uploadResp->json('h');
        if (!$handle) {
            $this->error('No header_handle returned: ' . $uploadResp->body());
            return self::FAILURE;
        }

        $this->info('Uploaded. Submitting template...');

        // 3. Submit the template with a DOCUMENT header + BODY.
        $response = Http::withToken($token)
            ->post("https://graph.facebook.com/{$version}/{$wabaId}/message_templates", [
                'name' => 'invoice_ready',
                'category' => 'UTILITY',
                'allow_category_change' => true,
                'language' => 'en',
                'components' => [
                    [
                        'type' => 'HEADER',
                        'format' => 'DOCUMENT',
                        'example' => ['header_handle' => [$handle]],
                    ],
                    [
                        'type' => 'BODY',
                        'text' => $bodyText,
                        'example' => ['body_text' => [$bodyExample]],
                    ],
                ],
            ]);

        if ($response->successful()) {
            $this->info('✅ Submitted — id: ' . ($response->json('id') ?? 'n/a'));
        } else {
            $this->error('❌ Failed (HTTP ' . $response->status() . '): ' . ($response->json('error.message') ?? 'unknown'));
            $this->line('Full response: ' . $response->body());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
