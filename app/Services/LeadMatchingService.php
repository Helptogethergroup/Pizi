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
    public function leadsForOwner(User $owner, int $minScore = 30): Collection
    {
        $properties = Property::where('owner_id', $owner->id)->with(['city', 'locality'])->get();

        if ($properties->isEmpty()) {
            return collect();
        }

        $propertyIds = $properties->pluck('id');
        $cityIds = $properties->pluck('city_id')->unique();

        // Pull leads from owner's properties OR generic leads matching owner's cities
        $leads = Lead::with(['property.city', 'property.locality', 'unlocks' => fn ($q) => $q->where('user_id', $owner->id)])
            ->where(function ($q) use ($propertyIds, $cityIds) {
                $q->whereIn('property_id', $propertyIds)
                  ->orWhere(function ($qb) use ($cityIds) {
                      // Generic leads (no specific property) but matching city preference
                      $qb->whereNull('property_id');
                  });
            })
            ->where(function ($q) use ($owner) {
                // Not locked OR locked by this owner
                $q->where('is_locked', false)
                  ->orWhere('locked_by_user_id', $owner->id);
            })
            ->latest()
            ->take(200)
            ->get();

        $walletBalance = $owner->wallet?->balance ?? 0;
        $pricing = LeadPricing::where('is_active', true)->pluck('credit_cost', 'lead_type')->toArray();

        return $leads
            ->map(function (Lead $lead) use ($properties, $walletBalance, $pricing) {
                // Find best-matching property of this owner for this lead
                $bestScore = 0;
                $bestProperty = null;
                foreach ($properties as $property) {
                    $s = $this->score($lead, $property);
                    if ($s > $bestScore) {
                        $bestScore = $s;
                        $bestProperty = $property;
                    }
                }

                $cost = $pricing[$lead->lead_type ?? 'direct'] ?? 0;
                $isUnlocked = $lead->unlocks->isNotEmpty();

                $lead->match_score = $bestScore;
                $lead->matched_property = $bestProperty;
                $lead->badge_info = $this->badge($bestScore);
                $lead->unlock_cost = $cost;
                $lead->is_unlocked = $isUnlocked;
                $lead->can_afford = $isUnlocked || $walletBalance >= $cost;

                return $lead;
            })
            ->filter(fn (Lead $lead) => $lead->match_score >= $minScore)
            ->sortByDesc(function (Lead $lead) {
                // Unlocked first, then by score
                return ($lead->is_unlocked ? 1000 : 0) + $lead->match_score;
            })
            ->values();
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