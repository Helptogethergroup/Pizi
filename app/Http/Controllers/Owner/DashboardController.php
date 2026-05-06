<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Property;

class DashboardController extends Controller
{
    public function index()
    {
        $owner = auth()->user();
        $stats = [
            'properties' => Property::where('owner_id', $owner->id)->count(),
            'active' => Property::where('owner_id', $owner->id)->where('is_active', true)->count(),
            'total_views' => (int) Property::where('owner_id', $owner->id)->sum('view_count'),
            'total_leads' => Lead::whereIn('property_id',
                Property::where('owner_id', $owner->id)->pluck('id'))->count(),
        ];

        $recentLeads = Lead::whereIn('property_id',
                Property::where('owner_id', $owner->id)->pluck('id'))
            ->with('property')
            ->latest()->take(10)->get();

        $properties = Property::where('owner_id', $owner->id)
            ->with(['city', 'locality'])
            ->latest()->take(5)->get();

        return view('owner.dashboard', compact('stats', 'recentLeads', 'properties'));
    }
}
