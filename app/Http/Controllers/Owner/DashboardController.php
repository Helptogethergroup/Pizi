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

        $properties = Property::whereIn('id', $propertyIds)
            ->with(['city', 'locality'])
            ->latest()->take(5)->get();

        return view('owner.dashboard', compact('stats', 'recentLeads', 'properties'));
    }
}