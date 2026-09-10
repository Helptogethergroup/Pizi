<?php

namespace App\Console\Commands;

use App\Models\AdLeadFormType;
use App\Models\Lead;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * One-time follow-up to meta:import-historical-leads: now that Owner/Tenant
 * form mappings exist in ad_lead_form_types, re-fetch each mapped form's
 * leads again (just to get the leadgen_id -> form_id link — the Lead table
 * itself doesn't store form_id) and update the matching backfilled Lead
 * rows from 'unknown' to the correct inquiry_type.
 *
 * Usage:
 *   php artisan meta:reclassify-historical-leads --dry-run
 *   php artisan meta:reclassify-historical-leads
 */
class ReclassifyHistoricalMetaLeads extends Command
{
    protected $signature = 'meta:reclassify-historical-leads {--dry-run : Preview counts without saving anything}';

    protected $description = 'Retroactively fix inquiry_type on already-imported Meta leads using ad_lead_form_types mappings';

    public function handle(): int
    {
        $token = config('ads_leads.meta_page_access_token');
        if (empty($token)) {
            $this->error('META_LEADS_PAGE_ACCESS_TOKEN is not set in .env.');
            return self::FAILURE;
        }

        $version = config('ads_leads.meta_api_version', 'v20.0');
        $dryRun = (bool) $this->option('dry-run');

        $mappings = AdLeadFormType::where('platform', 'meta')->get();
        if ($mappings->isEmpty()) {
            $this->warn('No Meta form mappings found in ad_lead_form_types — nothing to do.');
            return self::SUCCESS;
        }

        $this->info($dryRun ? 'DRY RUN — nothing will be saved.' : 'Reclassifying for real.');

        $totalUpdated = 0;
        $totalNotFound = 0;

        foreach ($mappings as $mapping) {
            $formId = $mapping->form_id;
            $this->line("\nForm {$formId} → {$mapping->inquiry_type}");

            $url = "https://graph.facebook.com/{$version}/{$formId}/leads";
            $params = ['access_token' => $token, 'limit' => 100];
            $after = null;

            do {
                if ($after) {
                    $params = ['access_token' => $token, 'limit' => 100, 'after' => $after];
                }

                $resp = Http::get($url, $params);
                if (!$resp->successful()) {
                    $this->error('  Failed to fetch leads: ' . $resp->body());
                    break;
                }

                foreach ($resp->json('data', []) as $leadData) {
                    $leadgenId = $leadData['id'];

                    $lead = Lead::where('source', 'meta_ads')
                        ->where('message', 'like', "%ref: {$leadgenId})%")
                        ->first();

                    if (!$lead) {
                        $totalNotFound++;
                        continue;
                    }

                    if ($lead->inquiry_type === $mapping->inquiry_type) {
                        continue; // already correct
                    }

                    $this->line("  {$lead->name} ({$lead->phone}) — unknown → {$mapping->inquiry_type}");

                    if (!$dryRun) {
                        $lead->inquiry_type = $mapping->inquiry_type;
                        $lead->save();
                    }

                    $totalUpdated++;
                }

                $after = $resp->json('paging.cursors.after');
                $hasNext = !empty($resp->json('paging.next'));
            } while ($after && $hasNext);
        }

        $this->info("\nDone. Updated: {$totalUpdated} | Leads not matched in DB: {$totalNotFound}");

        return self::SUCCESS;
    }
}
