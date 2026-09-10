<?php

namespace App\Http\Controllers\TeleCaller;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Visit;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Shared pool — every telecaller sees the same team-wide numbers
        // (not filtered to their own assigned_telecaller_id), matching the
        // shared "My Leads" list.
        $stats = [
            'total_assigned' => Lead::count(),
            'new' => Lead::where('status', 'new')->count(),
            'follow_ups_today' => Lead::whereDate('next_follow_up_at', today())->count(),
            'closed_won' => Lead::where('status', 'closed_won')->count(),
        ];

        $todaysFollowUps = Lead::whereDate('next_follow_up_at', today())
            ->with('property')->take(15)->get();

        // Top-priority leads: uncontacted first, oldest-waiting and
        // highest-budget first — so the most important call of the day is
        // always at the top, not just "newest".
        $priorityLeads = Lead::where('status', 'new')
            ->with('property')
            ->orderByDesc('budget_max')
            ->oldest('created_at')
            ->take(5)
            ->get();

        // Team-wide calls logged today (shared pool — no per-telecaller
        // "who called" field to filter on).
        $attendedToday = Lead::whereDate('called_at', today())->count();
        $callTarget = $user->daily_call_target ?: config('telecaller.default_daily_call_target', 20);

        return view('telecaller.dashboard', compact('stats', 'todaysFollowUps', 'priorityLeads', 'attendedToday', 'callTarget'));
    }
}
