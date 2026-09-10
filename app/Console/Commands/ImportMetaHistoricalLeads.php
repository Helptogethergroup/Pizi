<?php

namespace App\Console\Commands;

use App\Models\AdLeadFormType;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * One-time backfill: pulls every lead already sitting in Meta's Leads
 * Center (collected while the app's webhook wasn't delivering, e.g. while
 * the app was stuck in "Development" mode) and imports them the same way
 * the live webhook does — same de-dupe, same inquiry_type lookup via
 * AdLeadFormType, same telecaller round-robin assignment.
 *
 * Usage:
 *   php artisan meta:import-historical-leads --dry-run   (preview only)
 *   php artisan meta:import-historical-leads             (actually import)
 */
class ImportMetaHistoricalLeads extends Command
{
    protected $signature = 'meta:import-historical-leads {--dry-run : Preview counts without saving anything}';

    protected $description = 'Backfill leads already sitting in Meta Leads Center that the webhook never delivered';

    public function handle(): int
    {
        $token = config('ads_leads.meta_page_access_token');
        if (empty($token)) {
            $this->error('META_LEADS_PAGE_ACCESS_TOKEN is not set in .env — cannot call the Graph API.');
            return self::FAILURE;
        }

        $pageId = '1122973884229780';
        $version = config('ads_leads.meta_api_version', 'v20.0');
        $dryRun = (bool) $this->option('dry-run');

        $this->info($dryRun ? 'DRY RUN — nothing will be saved.' : 'Importing for real.');

        // 1. Find every lead form under the page.
        $formsResp = Http::get("https://graph.facebook.com/{$version}/{$pageId}/leadgen_forms", [
            'access_token' => $token,
            'fields' => 'id,name,status',
            'limit' => 100,
        ]);

        if (!$formsResp->successful()) {
            $this->error('Failed to fetch lead forms: ' . $formsResp->body());
            return self::FAILURE;
        }

        $forms = $formsResp->json('data', []);
        if (empty($forms)) {
            $this->warn('No lead forms found on this page.');
            return self::SUCCESS;
        }

        $this->info('Found ' . count($forms) . ' lead form(s): ' . collect($forms)->pluck('name')->implode(', '));

        $totalFetched = 0;
        $totalImported = 0;
        $totalSkippedDup = 0;
        $totalSkippedNoPhone = 0;

        foreach ($forms as $form) {
            $formId = $form['id'];
            $formName = $form['name'] ?? $formId;
            $inquiryType = AdLeadFormType::typeFor('meta', $formId) ?? 'unknown';

            $this->line("\nForm: {$formName} ({$formId}) — mapped type: {$inquiryType}");

            $url = "https://graph.facebook.com/{$version}/{$formId}/leads";
            $params = ['access_token' => $token, 'limit' => 100];
            $after = null;

            do {
                if ($after) {
                    $params = ['access_token' => $token, 'limit' => 100, 'after' => $after];
                }

                $resp = Http::get($url, $params);
                if (!$resp->successful()) {
                    $this->error("  Failed to fetch leads for this form: " . $resp->body());
                    break;
                }

                $leads = $resp->json('data', []);
                foreach ($leads as $leadData) {
                    $totalFetched++;

                    $fields = collect($leadData['field_data'] ?? [])
                        ->mapWithKeys(fn ($f) => [$f['name'] => $f['values'][0] ?? null]);

                    $name = $fields->get('full_name') ?? $fields->get('first_name') ?? 'Meta Ads Lead';
                    $phone = $fields->get('phone_number') ?? $fields->get('phone');
                    $email = $fields->get('email');
                    $leadgenId = $leadData['id'];
                    $createdTime = $leadData['created_time'] ?? null;

                    if (empty($phone)) {
                        $totalSkippedNoPhone++;
                        continue;
                    }

                    $phone = preg_replace('/[^0-9]/', '', $phone);

                    // De-dupe against ANY existing meta_ads lead with this phone —
                    // unlike the live webhook's 24h window, backfill can span months.
                    $exists = Lead::where('phone', $phone)->where('source', 'meta_ads')->exists();
                    if ($exists) {
                        $totalSkippedDup++;
                        continue;
                    }

                    $this->line("  + {$name} — {$phone}" . ($dryRun ? ' (dry run, not saved)' : ''));

                    if (!$dryRun) {
                        $telecaller = User::where('role', 'telecaller')
                            ->where('is_active', true)
                            ->withCount('assignedLeads')
                            ->orderBy('assigned_leads_count')
                            ->first();

                        $lead = Lead::create([
                            'name' => $name,
                            'phone' => $phone,
                            'email' => $email,
                            'source' => 'meta_ads',
                            'inquiry_type' => $inquiryType,
                            'status' => 'new',
                            'message' => "Backfilled from Meta Leads Center (ref: {$leadgenId})",
                            'assigned_telecaller_id' => $telecaller?->id,
                        ]);

                        if ($createdTime) {
                            // Preserve the real submission time instead of "now".
                            $lead->created_at = \Carbon\Carbon::parse($createdTime);
                            $lead->save();
                        }

                        if ($telecaller) {
                            try {
                                $telecaller->notify(new \App\Notifications\NewLeadAssigned($lead));
                            } catch (\Exception $e) {
                                // Non-fatal — don't stop the import over a notification failure.
                            }
                        }
                    }

                    $totalImported++;
                }

                $after = $resp->json('paging.cursors.after');
                $hasNext = !empty($resp->json('paging.next'));
            } while ($after && $hasNext);
        }

        $this->info("\nDone. Fetched: {$totalFetched} | Imported: {$totalImported} | Skipped (duplicate): {$totalSkippedDup} | Skipped (no phone): {$totalSkippedNoPhone}");

        return self::SUCCESS;
    }
}
