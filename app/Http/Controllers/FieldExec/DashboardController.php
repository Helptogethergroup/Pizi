<?php

namespace App\Http\Controllers\FieldExec;

use App\Http\Controllers\Controller;
use App\Models\Visit;

class DashboardController extends Controller
{
    public function index()
    {
        $execId = auth()->id();
        $today = today();

        $stats = [
            'today_total' => Visit::where('field_executive_id', $execId)
                ->whereDate('scheduled_at', $today)->count(),
            'today_done' => Visit::where('field_executive_id', $execId)
                ->whereDate('scheduled_at', $today)
                ->whereIn('outcome', ['closed', 'rejected', 'no_show'])->count(),
            'today_pending' => Visit::where('field_executive_id', $execId)
                ->whereDate('scheduled_at', $today)
                ->where('outcome', 'pending')->count(),
            'this_month_closed' => Visit::where('field_executive_id', $execId)
                ->whereMonth('updated_at', now()->month)
                ->where('outcome', 'closed')->count(),
            'tokens_collected' => Visit::where('field_executive_id', $execId)
                ->whereMonth('updated_at', now()->month)
                ->where('outcome', 'closed')
                ->sum('token_amount'),
        ];

        // Today's visits — sorted by scheduled time
        $todayVisits = Visit::where('field_executive_id', $execId)
            ->whereDate('scheduled_at', $today)
            ->with(['lead', 'property.locality'])
            ->orderBy('scheduled_at')
            ->get();

        // Upcoming (tomorrow + future)
        $upcomingVisits = Visit::where('field_executive_id', $execId)
            ->whereDate('scheduled_at', '>', $today)
            ->where('outcome', 'pending')
            ->with(['lead', 'property.locality'])
            ->orderBy('scheduled_at')
            ->take(10)
            ->get();

        return view('fieldexec.dashboard', compact('stats', 'todayVisits', 'upcomingVisits'));
    }
}