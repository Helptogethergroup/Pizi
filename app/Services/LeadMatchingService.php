<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadPricing;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Collection;

class LeadMatchingService
{
    /**
     * Calculate match score (0-100) between a lead and an owner's property.
     *
     * Weights:
     * - Location match: 40 pts (same locality = 40, same city = 20, else 0)
     * - Budget match:   30 pts (rent fits in lead's budget = 30, partial = 15)
     * - Gender match:   20 pts (exact match = 20, unisex either way = 15)
     * - Availability:   10 pts (rooms available = 10)
     */
    public function score(Lead $lead, Property $property): int
    {
        $score = 0;

        // 1. LOCATION (40 points)
        if ($lead->property_id && $lead->property_id === $property->id) {
            // Lead is for this exact property — perfect
            $score += 40;
        } elseif ($lead->preferred_locality && $property->locality
                  && stripos($property->locality->name, $lead->preferred_locality) !== false) {
            $score += 40; // same locality
        } elseif ($lead->preferred_city && $property->city
                  && stripos($property->city->name, $lead->preferred_city) !== false) {
            $score += 20; // same city only
        } elseif (!$lead->preferred_city && !$lead->preferred_locality) {
            $score += 10; // lead has no preference — neutral
        }

        // 2. BUDGET (30 points)
        if ($lead->budget_min && $lead->budget_max) {
            // Property's rent range fits within lead's budget
            if ($property->rent_min >= $lead->budget_min && $property->rent_max <= $lead->budget_max) {
                $score += 30;
            } elseif ($property->rent_min <= $lead->budget_max && $property->rent_max >= $lead->budget_min) {
                $score += 15; // overlapping but not perfect
            }
        } elseif ($lead->budget_max) {
            if ($property->rent_min <= $lead->budget_max) {
                $score += 25;
            }
        } else {
            $score += 15; // no budget = neutral
        }

        // 3. GENDER (20 points)
        if ($lead->preferred_gender) {
            if ($property->gender === $lead->preferred_gender) {
                $score += 20;
            } elseif ($property->gender === 'unisex' || $lead->preferred_gender === 'unisex') {
                $score += 15;
            }
        } else {
            $score += 10; // no preference = neutral
        }

        // 4. AVAILABILITY (10 points)
        if ($property->available_rooms > 0) {
            $score += 10;
        } elseif ($property->is_active) {
            $score += 3; // active but no rooms shown
        }

        return min($score, 100);
    }

    /**
     * Get classification badge for a score.
     */
    public function badge(int $score): array
    {
        return match (true) {
            $score >= 85 => ['label' => '🔥 Hot Match', 'class' => 'bg-rose-500 text-white'],
            $score >= 70 => ['label' => '⭐ Great Match', 'class' => 'bg-coral-500 text-white'],
            $score >= 50 => ['label' => '✓ Good Match', 'class' => 'bg-emerald-500 text-white'],
            $score >= 30 => ['label' => 'Possible Match', 'class' => 'bg-amber-100 text-amber-900'],
            default => ['label' => 'Low Match', 'class' => 'bg-ink-900/10 text-ink-900/60'],
        };
    }

    /**
     * Get all matched leads for a specific owner across all their properties.
     * Returns sorted collection with score + badge + affordability attached.
     */
 public function leadsForOwner($owner, int $limit = 60)
    {
        $properties = $owner->properties()->where('is_active', true)
            ->with(['city', 'locality'])->get();

        if ($properties->isEmpty()) return collect();

        $unlockedIds = \App\Models\LeadUnlock::where('owner_id', $owner->id)
            ->pluck('lead_id')->toArray();

        $lockedByOthers = \App\Models\LeadUnlock::where('owner_id', '!=', $owner->id)
            ->pluck('lead_id')->toArray();

        $ownerLocalities = $properties->pluck('locality.name')->filter()->unique()->toArray();
        $ownerCities = $properties->pluck('city.name')->filter()->unique()->toArray();

        $leads = \App\Models\Lead::whereNotIn('id', $lockedByOthers)
            ->where('status', '!=', 'closed_lost')
            ->latest()
            ->take($limit * 3)
            ->get();

        $wallet = $owner->wallet;

        $scored = $leads->map(function ($lead) use ($properties, $unlockedIds, $wallet, $ownerLocalities, $ownerCities) {
            $bestScore = 0;
            $bestProp = null;
            foreach ($properties as $prop) {
                $s = $this->score($lead, $prop);
                if ($s > $bestScore) {
                    $bestScore = $s;
                    $bestProp = $prop;
                }
            }

            // AREA MATCH BONUS
            $areaMatch = false;
            if ($lead->preferred_locality && in_array($lead->preferred_locality, $ownerLocalities)) {
                $bestScore = min(100, $bestScore + 15);
                $areaMatch = true;
            } elseif ($lead->preferred_city && in_array($lead->preferred_city, $ownerCities)) {
                $bestScore = min(100, $bestScore + 5);
            }

            $lead->match_score = $bestScore;
            $lead->matched_property = $bestProp;
            $lead->is_unlocked = in_array($lead->id, $unlockedIds);
            $lead->area_match = $areaMatch;

            $type = $lead->lead_type ?? 'direct';
            $pricing = \App\Models\LeadPricing::where('lead_type', $type)
                ->where('is_active', true)->first();
            $lead->unlock_cost = $pricing?->credit_cost ?? 0;
            $lead->affordable = $wallet && $wallet->balance >= $lead->unlock_cost;

            return $lead;
        });

        // Sort: area-matched first, then by score
        return $scored->sortByDesc(function ($lead) {
            return ($lead->area_match ? 1000 : 0) + $lead->match_score;
        })->values();
    }

    /**
     * Find unmatched leads (no owner has a property matching this lead well).
     * Used by admin dashboard to spot opportunities for new listings.
     */
    public function unmatchedLeads(int $minScoreThreshold = 30): Collection
    {
        $allActiveOwners = User::where('role', 'owner')
            ->where('is_active', true)
            ->whereHas('properties', fn ($q) => $q->where('is_active', true))
            ->with('properties.city', 'properties.locality')
            ->get();

        $recentLeads = Lead::whereNull('locked_by_user_id')
            ->where('created_at', '>=', now()->subDays(7))
            ->with('property')
            ->latest()
            ->take(100)
            ->get();

        return $recentLeads->filter(function (Lead $lead) use ($allActiveOwners, $minScoreThreshold) {
            foreach ($allActiveOwners as $owner) {
                foreach ($owner->properties as $property) {
                    if ($this->score($lead, $property) >= $minScoreThreshold) {
                        return false; // someone matches well enough
                    }
                }
            }
            return true; // no good match found
        })->values();
    }
}