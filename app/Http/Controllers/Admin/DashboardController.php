<?php

namespace App\Http\Controllers\Admin;

use App\Services\LeadMatchingService;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Property;
use App\Models\User;
use App\Models\Visit;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_properties' => Property::count(),
            'active_properties' => Property::where('is_active', true)->count(),
            'pending_verification' => Property::where('is_verified', false)->count(),
            'total_leads' => Lead::count(),
            'new_leads' => Lead::where('status', 'new')->count(),
            'closed_won' => Lead::where('status', 'closed_won')->count(),
            'total_owners' => User::where('role', 'owner')->count(),
            'telecallers' => User::where('role', 'telecaller')->where('is_active', true)->count(),
        ];

        $leadsByStatus = Lead::selectRaw('status, count(*) as total')
            ->groupBy('status')->pluck('total', 'status')->toArray();

        $recentLeads = Lead::with('property', 'telecaller')->latest()->take(15)->get();

        // Conversion rate
        $totalDecided = Lead::whereIn('status', ['closed_won', 'closed_lost'])->count();
        $won = Lead::where('status', 'closed_won')->count();
        $stats['conversion_rate'] = $totalDecided > 0
            ? round(($won / $totalDecided) * 100, 1)
            : 0;

        // Find leads with no good owner match (opportunity to acquire new owners)
        $unmatchedLeads = app(LeadMatchingService::class)->unmatchedLeads(30);
      return view('admin.dashboard', compact('stats', 'leadsByStatus', 'recentLeads', 'unmatchedLeads'));
    }
}
