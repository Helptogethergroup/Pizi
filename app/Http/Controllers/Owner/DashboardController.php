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

        $recentLeads = Lead::whereIn('property_id', $propertyIds)
            ->with('property')
            ->latest()->take(10)->get();

        // A lead's phone and email are what owners pay credits for: show them
        // here only for leads this owner (or their team) has unlocked.
        $unlocked = Lead::unlockedIdsFor($user);
        $recentLeads->each(function ($lead) use ($unlocked) {
            $lead->contact_visible = in_array((int) $lead->id, $unlocked, true);
            if (!$lead->contact_visible) {
                $lead->phone = Lead::maskPhone($lead->phone);
                $lead->email = null;
            }
        });

        $properties = Property::whereIn('id', $propertyIds)
            ->with(['city', 'locality'])
            ->latest()->take(5)->get();

        return view('owner.dashboard', compact('stats', 'recentLeads', 'properties'));
    }
}