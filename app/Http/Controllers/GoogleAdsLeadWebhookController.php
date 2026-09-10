<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Receives leads from Google Ads Lead Form Extensions.
 *
 * Setup needed on Google Ads' side (Tools → Conversions/Lead form assets
 * → the lead form's delivery settings):
 *   1. Add a "Webhook" delivery destination pointing at
 *      POST https://pizi.in/webhooks/google-ads-leads
 *   2. GOOGLE_ADS_LEADS_WEBHOOK_KEY in .env — any string you invent,
 *      pasted into the same Google Ads webhook setup screen. Google
 *      sends it back as `google_key` on every lead so we can verify the
 *      request is genuinely theirs.
 *
 * Every lead arrives generic/unclassified — source is tagged 'google_ads'
 * and the telecaller who picks it up decides owner vs. tenant on the call.
 */
class GoogleAdsLeadWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->all();
        Log::info('Google Ads lead webhook received', $payload);

        $expectedKey = config('ads_leads.google_webhook_key');
        if (empty($expectedKey) || ($payload['google_key'] ?? null) !== $expectedKey) {
            Log::warning('Google Ads lead webhook rejected — key mismatch');
            return response('Forbidden', 403);
        }

        $columns = collect($payload['user_column_data'] ?? [])
            ->mapWithKeys(fn ($c) => [$c['column_id'] ?? $c['column_name'] ?? '' => $c['string_value'] ?? null]);

        $name = $columns->get('FULL_NAME') ?? $columns->get('Full name') ?? 'Google Ads Lead';
        $phone = $columns->get('PHONE_NUMBER') ?? $columns->get('Phone number');
        $email = $columns->get('EMAIL') ?? $columns->get('Email');

        if (empty($phone)) {
            Log::warning('Google Ads lead has no phone number, skipping', ['columns' => $columns]);
            return response('OK', 200);
        }

        // Owner vs. tenant is known from WHICH lead form they filled —
        // if this form_id hasn't been registered yet, falls back to 'unknown'.
        $inquiryType = \App\Models\AdLeadFormType::typeFor('google', $payload['form_id'] ?? null) ?? 'unknown';

        $this->createLead($name, $phone, $email, 'google_ads', $payload['lead_id'] ?? 'unknown', $inquiryType);

        return response('OK', 200);
    }

    private function createLead(string $name, string $phone, ?string $email, string $source, string $externalId, string $inquiryType = 'unknown'): void
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        $duplicate = Lead::where('phone', $phone)
            ->where('source', $source)
            ->where('created_at', '>=', now()->subDay())
            ->first();
        if ($duplicate) {
            return;
        }

        $telecaller = User::where('role', 'telecaller')
            ->where('is_active', true)
            ->withCount('assignedLeads')
            ->orderBy('assigned_leads_count')
            ->first();

        $lead = Lead::create([
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'source' => $source,
            'inquiry_type' => $inquiryType,
            'status' => 'new',
            'message' => "Auto-imported from {$source} (ref: {$externalId})",
            'assigned_telecaller_id' => $telecaller?->id,
        ]);

        if ($telecaller) {
            try {
                $telecaller->notify(new \App\Notifications\NewLeadAssigned($lead));
            } catch (\Exception $e) {
                Log::warning('Ad-lead assignment notification failed: ' . $e->getMessage());
            }
        }
    }
}
