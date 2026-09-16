<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Receives leads from Meta (Facebook/Instagram) Lead Ads.
 *
 * Setup needed on Meta's side (Business Suite → the Page running ads):
 *   1. A webhook subscription on the Page for the "leadgen" field,
 *      pointing at GET/POST https://pizi.in/webhooks/meta-leads
 *   2. META_LEADS_VERIFY_TOKEN in .env — any string you invent, typed
 *      into Meta's webhook setup screen too (used only for the one-time
 *      GET verification handshake below)
 *   3. META_LEADS_PAGE_ACCESS_TOKEN in .env — a Page Access Token with
 *      `leads_retrieval` permission (from Business Suite, NOT the
 *      WhatsApp Cloud API token used elsewhere in this app)
 *
 * Every lead arrives generic/unclassified — source is tagged 'meta_ads'
 * and the telecaller who picks it up decides owner vs. tenant on the call.
 */
class MetaLeadWebhookController extends Controller
{
    /**
     * One-time handshake Meta calls when you first save the webhook URL.
     */
    public function verify(Request $request)
    {
        if (
            $request->query('hub_mode') === 'subscribe'
            && $request->query('hub_verify_token') === config('ads_leads.meta_verify_token')
        ) {
            return response($request->query('hub_challenge'), 200);
        }

        return response('Forbidden', 403);
    }

    /**
     * Meta calls this every time a new lead is submitted on an ad.
     */
    public function handle(Request $request)
    {
        $payload = $request->all();
        Log::info('Meta lead webhook received', $payload);

        $entries = $payload['entry'] ?? [];

        foreach ($entries as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                if (($change['field'] ?? null) !== 'leadgen') {
                    continue;
                }

                $leadgenId = $change['value']['leadgen_id'] ?? null;
                $formId = $change['value']['form_id'] ?? null;
                if ($leadgenId) {
                    $this->importLead($leadgenId, $formId);
                }
            }
        }

        // Meta requires a 200 quickly regardless of processing outcome,
        // or it will retry aggressively and eventually disable the webhook.
        return response('EVENT_RECEIVED', 200);
    }

    private function importLead(string $leadgenId, ?string $formId = null): void
    {
        $token = config('ads_leads.meta_page_access_token');
        if (empty($token)) {
            Log::warning('Meta lead skipped — META_LEADS_PAGE_ACCESS_TOKEN not configured', ['leadgen_id' => $leadgenId]);
            return;
        }

        try {
            $response = Http::get('https://graph.facebook.com/' . config('ads_leads.meta_api_version', 'v20.0') . "/{$leadgenId}", [
                'access_token' => $token,
            ]);

            if (!$response->successful()) {
                Log::warning('Meta lead fetch failed', ['leadgen_id' => $leadgenId, 'body' => $response->body()]);
                return;
            }

            $fields = collect($response->json('field_data', []))
                ->mapWithKeys(fn ($f) => [$f['name'] => $f['values'][0] ?? null]);

            $name = $fields->get('full_name') ?? $fields->get('first_name') ?? 'Meta Ads Lead';
            $phone = $fields->get('phone_number') ?? $fields->get('phone');
            $email = $fields->get('email');

            // Meta's field key matches the question text (e.g. a "Which
            // city are you looking in?" question becomes some snake_case
            // key containing "city") — match loosely so this keeps working
            // even if the exact wording of the question changes later.
            $cityKey = $fields->keys()->first(fn ($k) => str_contains(strtolower($k), 'city'));
            $preferredCity = $cityKey ? $fields->get($cityKey) : null;

            if (empty($phone)) {
                Log::warning('Meta lead has no phone number, skipping', ['leadgen_id' => $leadgenId, 'fields' => $fields]);
                return;
            }

            // Owner vs. tenant is known from WHICH lead form they filled —
            // if this form_id hasn't been registered yet, default to
            // 'tenant' (the vast majority of ad leads are) rather than
            // 'unknown', which never reaches any owner's dashboard at all.
            $inquiryType = \App\Models\AdLeadFormType::typeFor('meta', $formId) ?? 'tenant';

            $this->createLead($name, $phone, $email, 'meta_ads', $leadgenId, $inquiryType, $preferredCity);
        } catch (\Exception $e) {
            Log::error('Meta lead import exception: ' . $e->getMessage());
        }
    }

    /**
     * Shared with GoogleAdsLeadWebhookController — kept here as a private
     * method since it's only a few lines; not worth a shared trait yet.
     */
    private function createLead(string $name, string $phone, ?string $email, string $source, string $externalId, string $inquiryType = 'tenant', ?string $preferredCity = null): void
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // De-dupe: same phone within the last 24h from ads = don't double-create
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
            'preferred_city' => $preferredCity,
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
