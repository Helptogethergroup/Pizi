<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Visit;

class FieldTrackerController extends Controller
{
    public function index()
    {
        $today = today();

        // All field execs with today's stats
        $execs = User::where('role', 'field_executive')
            ->where('is_active', true)
            ->withCount([
                'fieldVisits as today_total' => fn ($q) => $q->whereDate('scheduled_at', $today),
                'fieldVisits as today_done' => fn ($q) => $q->whereDate('scheduled_at', $today)
                    ->whereIn('outcome', ['closed', 'rejected', 'no_show']),
                'fieldVisits as today_closed' => fn ($q) => $q->whereDate('scheduled_at', $today)
                    ->where('outcome', 'closed'),
            ])
            ->get();

        // Active visits (checked in but not checked out) — "live" execs
        $activeVisits = Visit::whereNotNull('checked_in_at')
            ->whereNull('checked_out_at')
            ->where('outcome', 'pending')
            ->with(['fieldExecutive', 'lead', 'property.locality'])
            ->latest('checked_in_at')
            ->get();

        // Today's all visits with check-in info
        $todaysVisits = Visit::whereDate('scheduled_at', $today)
            ->with(['fieldExecutive', 'lead', 'property.locality'])
            ->orderBy('scheduled_at')
            ->get();

        // This week's performance
        $weekStats = Visit::whereBetween('scheduled_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->selectRaw('field_executive_id, COUNT(*) as total, 
                        SUM(CASE WHEN outcome = "closed" THEN 1 ELSE 0 END) as closed,
                        SUM(CASE WHEN outcome = "closed" THEN token_amount ELSE 0 END) as tokens')
            ->groupBy('field_executive_id')
            ->with('fieldExecutive')
            ->get();

        return view('admin.field-tracker', compact('execs', 'activeVisits', 'todaysVisits', 'weekStats'));
    }
}