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

        // The dashboard only says that a lead came in. A lead's phone and email are
        // what owners pay credits for, so they are never put on this page: they are
        // on the Leads page, after the owner unlocks the lead.
        $recentLeads->each(function ($lead) {
            $lead->phone = null;
            $lead->email = null;
        });

        $properties = Property::whereIn('id', $propertyIds)
            ->with(['city', 'locality'])
            ->latest()->take(5)->get();

        return view('owner.dashboard', compact('stats', 'recentLeads', 'properties'));
    }
}