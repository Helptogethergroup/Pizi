<?php

namespace App\Http\Controllers\TeleCaller;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Visit;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        $stats = [
            'total_assigned' => Lead::where('assigned_telecaller_id', $userId)->count(),
            'new' => Lead::where('assigned_telecaller_id', $userId)->where('status', 'new')->count(),
            'follow_ups_today' => Lead::where('assigned_telecaller_id', $userId)
                ->whereDate('next_follow_up_at', today())->count(),
            'closed_won' => Lead::where('assigned_telecaller_id', $userId)
                ->where('status', 'closed_won')->count(),
        ];

        $todaysFollowUps = Lead::where('assigned_telecaller_id', $userId)
            ->whereDate('next_follow_up_at', today())
            ->with('property')->take(15)->get();

        $newLeads = Lead::where('assigned_telecaller_id', $userId)
            ->where('status', 'new')
            ->with('property')->latest()->take(15)->get();

        return view('telecaller.dashboard', compact('stats', 'todaysFollowUps', 'newLeads'));
    }
}
