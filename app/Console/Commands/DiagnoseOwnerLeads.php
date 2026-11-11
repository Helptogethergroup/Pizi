<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Models\LeadUnlock;
use App\Models\User;
use App\Services\LeadMatchingService;
use Illuminate\Console\Command;

/**
 * Explains why an owner sees few leads: every lead is checked against the same rules
 * LeadMatchingService::leadsForOwner uses, and the FIRST rule that hides it is counted.
 * Read-only: nothing is changed.
 *
 *   php artisan leads:diagnose owner@example.com
 */
class DiagnoseOwnerLeads extends Command
{
    protected $signature = 'leads:diagnose {email : Email of the owner (or PG manager)} {--examples=3 : Sample leads to print for each reason}';

    protected $description = 'Show, rule by rule, why leads are hidden from an owner';

    public function handle(LeadMatchingService $matcher): int
    {
        $owner = User::where('email', $this->argument('email'))->first();
        if (! $owner) {
            $this->error('No user with that email.');

            return self::FAILURE;
        }

        $properties = $owner->leadProperties()->with(['city', 'locality'])->get();
        $active = $properties->where('is_active', true);
        $cities = $active->pluck('city.name')->filter()->unique()->values()->all();

        $this->info("Owner: #{$owner->id} {$owner->name} ({$owner->role})  test account: ".($owner->is_test_account ? 'yes' : 'no'));
        $this->line('Properties assigned: '.$properties->count().', active: '.$active->count().', inactive: '.($properties->count() - $active->count()));
        $this->line('Cities of the active properties: '.($cities ? implode(', ', $cities) : 'NONE'));
        foreach ($active->groupBy('city.name') as $city => $group) {
            $this->line("   - ".($city ?: '(no city set)').': '.$group->count().' PG');
        }
        $this->line('Settings: area_mode='.config('leads.area_mode').', max_age_days='.config('leads.max_age_days'));
        $this->newLine();

        if ($active->isEmpty()) {
            $this->warn('No ACTIVE property: the owner sees no leads at all. Activate at least one property.');

            return self::SUCCESS;
        }

        $teamOwnerId = $owner->role === 'pg_manager' ? $owner->owner_id : $owner->id;
        $mine = LeadUnlock::where(fn ($q) => $q->where('user_id', $owner->id)->orWhere('owner_id', $teamOwnerId))->pluck('lead_id')->all();
        $lockedByOthers = LeadUnlock::whereHas('user', fn ($q) => $q->where('is_test_account', false))->pluck('lead_id')->all();
        $maxAge = (int) config('leads.max_age_days', 60);
        $propertyIds = $properties->pluck('id')->map(fn ($i) => (int) $i)->all();

        $reasons = [];
        $examples = [];
        $visible = 0;
        $total = 0;
        $tester = $matcher->testerSeesAll($owner);
        if ($tester) {
            $this->line('This is a TEST account: the age, "not interested", unanswered, unverified-Facebook and "already taken" rules are skipped for it (LEADS_TEST_SEES_ALL).');
            $this->newLine();
        }

        Lead::with('property.city')->orderByDesc('id')->chunk(500, function ($leads) use (&$reasons, &$examples, &$visible, &$total, $mine, $lockedByOthers, $maxAge, $cities, $active, $propertyIds, $matcher, $tester) {
            foreach ($leads as $lead) {
                $total++;
                $why = $this->firstReasonHidden($lead, $mine, $lockedByOthers, $maxAge, $cities, $active, $propertyIds, $matcher, $tester);
                if ($why === null) {
                    $visible++;
                    continue;
                }
                $reasons[$why] = ($reasons[$why] ?? 0) + 1;
                $examples[$why][] = $lead;
            }
        });

        $this->info("Leads in the database: {$total}");
        $rows = [['SHOWN to this owner', $visible]];
        arsort($reasons);
        foreach ($reasons as $why => $n) {
            $rows[] = [$why, $n];
        }
        $this->table(['What happens to the lead', 'Leads'], $rows);

        $real = $matcher->leadsForOwner($owner, 60)->count();
        $this->line("Cross-check, what the Leads page really lists for this owner: {$real}"
            .($real === $visible ? ' (same as above)' : " (differs from {$visible}: the page also stops after the newest 360 leads of the city)"));

        $n = (int) $this->option('examples');
        foreach ($reasons as $why => $count) {
            $this->newLine();
            $this->line("<comment>{$why}</comment>  ({$count})");
            foreach (array_slice($examples[$why], 0, $n) as $l) {
                $this->line(sprintf('   #%d  %s  source=%s type=%s status=%s call=%s tries=%s  city="%s" locality="%s" property=%s  %s',
                    $l->id, $l->created_at?->format('d M y'), $l->source, $l->lead_type, $l->status, $l->call_status ?: '-', $l->call_attempts ?? '-',
                    $l->preferred_city, $l->preferred_locality, $l->property_id ? ($l->property?->city?->name ?: 'PG#'.$l->property_id) : '-', ''));
            }
        }

        return self::SUCCESS;
    }

    /** The first rule (same order as the Leads page) that hides this lead, or null when it is shown. */
    private function firstReasonHidden(Lead $lead, array $mine, array $lockedByOthers, int $maxAge, array $cities, $active, array $propertyIds, LeadMatchingService $matcher, bool $tester = false): ?string
    {
        $unlocked = in_array($lead->id, $mine, true) || $tester; // a test account is not held to the quality rules

        if (in_array($lead->status, ['closed_lost', 'junk'], true)) {
            return '1. Marked junk / closed lost';
        }
        if ($lead->inquiry_type === 'owner') {
            return '2. Someone who wants to LIST a PG (not a tenant)';
        }
        if (! $unlocked) {
            if (in_array($lead->status, ['closed_won', 'not_interested'], true) || $lead->call_status === 'not_interested') {
                return '3. Telecaller: not interested / already closed';
            }
            if (($lead->call_attempts ?? 0) >= 3 && ! in_array($lead->call_status, ['contacted', 'interested', 'converted'], true)) {
                return '4. Three calls, nobody answered';
            }
            if ($maxAge > 0 && $lead->created_at && $lead->created_at->lt(now()->subDays($maxAge))) {
                return "5. Older than {$maxAge} days";
            }
        }
        if (! $tester && $lead->source === 'meta_ads' && ! in_array($lead->lead_type, ['verified', 'converted'], true)) {
            return '6. Facebook/Instagram lead not yet verified by a telecaller';
        }
        if (! $unlocked && in_array($lead->id, $lockedByOthers, true)) {
            return '7. Already unlocked (taken) by another real owner';
        }

        // the city rule of the Leads page
        $city = trim((string) $lead->preferred_city);
        if ($lead->property_id) {
            $leadCity = $lead->property?->city?->name;
            if (! $leadCity || ! in_array($leadCity, $cities, true)) {
                return '8. Lead is for a PG in a city where this owner has no PG ('.($leadCity ?: 'city unknown').')';
            }
        } else {
            $matches = $lead->preferred_city === null;
            foreach ($cities as $c) {
                if ($city !== '' && stripos($city, $c) !== false) {
                    $matches = true;
                }
            }
            if (! $matches) {
                return $city === ''
                    ? '9. No PG and no city written on the lead (nobody knows where the person wants to stay)'
                    : "10. Lead wants \"{$city}\", not one of this owner's cities";
            }
        }

        if (! $tester && ! $matcher->isRelevantToOwner($lead, $active, $propertyIds, $mine)) {
            return '11. Held back by LEADS_AREA_MODE ('.config('leads.area_mode').')';
        }

        return null;
    }
}
