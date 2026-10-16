<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Property;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $propertyIds = $user->getManagedPropertyIds();

        $stats = [
            'properties' => Property::whereIn('id', $propertyIds)->count(),
            'active' => Property::whereIn('id', $propertyIds)->where('is_active', true)->count(),
            'total_views' => (int) Property::whereIn('id', $propertyIds)->sum('view_count'),
            'total_leads' => Lead::whereIn('property_id', $propertyIds)->count(),
        ];

        // Only leads this owner (or their team) already unlocked, newest unlock first.
        // Even for these the phone and email are not printed on the dashboard: they
        // are on the Leads page, so the number lives in exactly one place.
        $recentLeads = collect();
        if ($user->hasFeature('leads')) {
            $ids = Lead::recentUnlockedIdsFor($user, 10);
            $recentLeads = Lead::whereIn('id', $ids)->with('property')->get()
                ->sortBy(fn ($lead) => array_search((int) $lead->id, $ids, true))
                ->values();
            $recentLeads->each(function ($lead) {
                $lead->phone = null;
                $lead->email = null;
            });
        }

        // The newest matched leads this owner has NOT unlocked yet. Name and area only;
        // the number stays hidden until the owner unlocks the lead.
        $lockedLeads = collect();
        if ($user->hasFeature('leads')) {
            $lockedLeads = app(\App\Services\LeadMatchingService::class)
                ->leadsForOwner($user, 60)
                ->where('is_unlocked', false)
                ->sortByDesc('created_at')
                ->take(10)
                ->values();
            $lockedLeads->each(function ($lead) {
                $lead->phone = null;
                $lead->email = null;
            });
        }

        $properties = Property::whereIn('id', $propertyIds)
            ->with(['city', 'locality'])
            ->latest()->take(5)->get();

        return view('owner.dashboard', compact('stats', 'recentLeads', 'lockedLeads', 'properties'));
    }
}