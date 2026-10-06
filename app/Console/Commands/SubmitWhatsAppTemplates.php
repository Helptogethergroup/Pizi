<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Submits new WhatsApp template proposals straight to Meta via the Cloud
 * API's Template Management endpoint — no manual form-filling in Business
 * Manager. Meta still runs its own review after this; this command only
 * automates the *submission* step, not the approval itself.
 *
 * Usage:
 *   php artisan whatsapp:submit-templates                # list, don't send
 *   php artisan whatsapp:submit-templates --send          # actually submit all
 *   php artisan whatsapp:submit-templates --send --only=vacant_room_alert,property_verified
 */
class SubmitWhatsAppTemplates extends Command
{
    protected $signature = 'whatsapp:submit-templates
                             {--send : Actually submit to Meta. Without this flag, it only previews.}
                             {--only= : Comma-separated template names to submit (default: all)}';

    protected $description = 'Submit the proposed new WhatsApp templates to Meta for review';

    /**
     * Every proposed template from the roadmap. {{1}}, {{2}}... are Meta's
     * numbered placeholders; "example" values are REQUIRED by Meta so
     * reviewers can see real-looking sample text.
     */
    private function templates(): array
    {
        return [
            'vacant_room_alert' => [
                'category' => 'UTILITY',
                'body' => "Hi {{1}}, your PG \"{{2}}\" has {{3}} room(s) vacant for the last {{4}} days.\n\nUpdate photos or pricing to refresh your listing — this improves your chances of getting new leads.",
                'example' => ['Rajesh', 'Sunrise PG', '2', '18'],
            ],
            'property_verified_alert' => [
                'category' => 'UTILITY',
                'body' => "Hi {{1}}, your property \"{{2}}\" has been ✅ verified and is now live on the site.\n\nView it here: {{3}}\n\nThank you for listing with Pizi.",
                'example' => ['Rajesh', 'Sunrise PG', 'https://pizi.in/pg/sunrise-pg'],
            ],
            'tenant_rent_overdue_owner_alert' => [
                'category' => 'UTILITY',
                'body' => "Hi {{1}}, your tenant {{2}} (Room {{3}}) has a pending rent of ₹{{4}}, overdue by {{5}} days.\n\nOpen your dashboard to follow up.",
                'example' => ['Rajesh', 'Amit Kumar', '204', '8500', '5'],
            ],
            'wallet_low_balance' => [
                'category' => 'UTILITY',
                'body' => "Hi {{1}}, your Pizi wallet balance is down to ₹{{2}}.\n\nRecharge now to keep receiving new leads.",
                'example' => ['Rajesh', '50'],
            ],
            'complaint_resolved' => [
                'category' => 'UTILITY',
                'body' => "Hi {{1}}, your complaint \"{{2}}\" has been resolved.\n\nIf the issue persists, you can raise it again from your dashboard.",
                'example' => ['Amit', 'Water leakage in bathroom'],
            ],
            'agreement_renewal_reminder' => [
                'category' => 'UTILITY',
                'body' => "Hi {{1}}, your rent agreement is expiring on {{2}}.\n\nPlease contact your PG owner to get it renewed.",
                'example' => ['Amit', '15 Oct 2026'],
            ],
            'move_out_refund_status' => [
                'category' => 'UTILITY',
                'body' => "Hi {{1}}, we've received your move-out request. Your security deposit of ₹{{2}} will be refunded within {{3}} days.",
                'example' => ['Amit', '10000', '7'],
            ],
            'admin_daily_summary' => [
                'category' => 'UTILITY',
                'body' => "Pizi Daily Summary — {{1}}\n\nNew signups: {{2}}\nNew leads: {{3}}\nPayments received: ₹{{4}}\n\nHave a great evening.",
                'example' => ['1 Sep 2026', '5', '23', '48000'],
            ],
            'payment_gateway_failure_alert' => [
                'category' => 'UTILITY',
                'body' => "⚠️ Alert: {{2}} payments have failed in the last {{1}} hours (gateway: {{3}}).\n\nPlease check immediately.",
                'example' => ['2', '6', 'Razorpay'],
            ],
            'tenant_kyc_reminder' => [
                'category' => 'UTILITY',
                'body' => "Hi {{1}}, your KYC documents are still pending on Pizi.\n\nComplete it here: {{2}} — it only takes a minute.",
                'example' => ['Amit', 'https://pizi.in/tenant/kyc'],
            ],
            'owner_wallet_credited_v3' => [
                'category' => 'MARKETING',
                'body' => "Hi {{1}}, your Pizi account has been credited with {{2}} credits. Login to your dashboard to view and use them for unlocking tenant leads.",
                'example' => ['Rajesh', '500'],
                'button' => ['text' => 'Login to Pizi', 'url' => 'https://pizi.in/login'],
            ],
            // v2 — "owner_daily_lead_count" (no suffix) already exists on
            // Meta's side from earlier failed attempts (name+language is
            // now taken), so this has to go under a new name.
            'owner_daily_lead_count_v2' => [
                'category' => 'MARKETING',
                'body' => "Hi {{1}}, you received {{2}} new tenant lead(s) today on Pizi ({{3}}).\n\nLogin to your dashboard to view and unlock them before another owner does.",
                'example' => ['Rajesh', '5', '17 Sep 2026'],
                'button' => ['text' => 'Login to Pizi', 'url' => 'https://pizi.in/login'],
            ],
        ];
    }

    public function handle(): int
    {
        $wabaId = config('whatsapp.waba_id');
        $token = config('whatsapp.access_token');

        if (!$wabaId) {
            $this->error('WHATSAPP_WABA_ID missing from .env — WhatsApp Manager → API Setup page (same page as Phone Number ID) me "WhatsApp Business Account ID" dikhega, wahi daalo.');
            return self::FAILURE;
        }

        $all = $this->templates();
        $only = $this->option('only');
        $selected = $only
            ? array_intersect_key($all, array_flip(array_map('trim', explode(',', $only))))
            : $all;

        if (empty($selected)) {
            $this->error('Koi matching template nahi mila. --only me exact naam check karo.');
            return self::FAILURE;
        }

        $send = $this->option('send');
        $this->info($send ? 'Submitting to Meta...' : 'Preview mode (kuch bhi bheja nahi ja raha — --send lagao asli submit ke liye):');
        $this->newLine();

        foreach ($selected as $name => $tpl) {
            $this->line("→ <fg=cyan>{$name}</> ({$tpl['category']})");

            if (!$send) {
                $this->line('  ' . str_replace("\n", ' ⏎ ', $tpl['body']));
                $this->newLine();
                continue;
            }

            $components = [
                [
                    'type' => 'BODY',
                    'text' => $tpl['body'],
                    'example' => [
                        'body_text' => [$tpl['example']],
                    ],
                ],
            ];

            // Optional static URL button (same link on every send — not a
            // dynamic {{1}} button, so no per-message parameter needed).
            if (!empty($tpl['button'])) {
                $components[] = [
                    'type' => 'BUTTONS',
                    'buttons' => [[
                        'type' => 'URL',
                        'text' => $tpl['button']['text'],
                        'url' => $tpl['button']['url'],
                    ]],
                ];
            }

            $response = Http::withToken($token)
                ->post("https://graph.facebook.com/" . config('whatsapp.api_version', 'v20.0') . "/{$wabaId}/message_templates", [
                    'name' => $name,
                    'category' => $tpl['category'],
                    'allow_category_change' => true,
                    'language' => 'en',
                    'components' => $components,
                ]);

            if ($response->successful()) {
                $this->line("  ✅ Submitted — id: " . ($response->json('id') ?? 'n/a'));
            } else {
                $this->line("  ❌ Failed (HTTP {$response->status()}): " . ($response->json('error.message') ?? 'unknown'));
                $this->line("  Full response: " . $response->body());
            }
            $this->newLine();
        }

        return self::SUCCESS;
    }
}
