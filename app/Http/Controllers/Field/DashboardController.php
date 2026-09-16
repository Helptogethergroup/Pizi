<?php

namespace App\Http\Controllers\Field;

use App\Http\Controllers\Controller;
use App\Models\FieldVisit;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        $todayVisits = FieldVisit::where('field_executive_id', $userId)
            ->whereDate('scheduled_at', today())
            ->with('property.city', 'property.locality')
            ->orderBy('scheduled_at')
            ->get();

        // Scheduled visits whose time has already passed — surfaced
        // separately so they don't get lost among today's other visits.
        $missedVisits = FieldVisit::where('field_executive_id', $userId)
            ->where('status', 'scheduled')
            ->where('scheduled_at', '<', now())
            ->with('property.city', 'property.locality')
            ->orderBy('scheduled_at')
            ->get();

        $upcomingVisits = FieldVisit::where('field_executive_id', $userId)
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->whereDate('scheduled_at', '>', today())
            ->with('property.city', 'property.locality')
            ->orderBy('scheduled_at')
            ->take(10)
            ->get();

        $stats = [
            'today_count' => $todayVisits->count(),
            'today_completed' => $todayVisits->where('status', 'completed')->count(),
            'pending_total' => FieldVisit::where('field_executive_id', $userId)
                ->whereIn('status', ['scheduled', 'in_progress'])->count(),
            'completed_total' => FieldVisit::where('field_executive_id', $userId)
                ->where('status', 'completed')->count(),
        ];

        // Performance — this week / this month, plus a rough average time
        // spent per completed visit (check-in to check-out).
        $completedThisWeek = FieldVisit::where('field_executive_id', $userId)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->get();

        $completedThisMonth = FieldVisit::where('field_executive_id', $userId)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        $propertiesVerifiedTotal = FieldVisit::where('field_executive_id', $userId)
            ->where('status', 'completed')
            ->where('address_verified', true)
            ->where('amenities_verified', true)
            ->where('rooms_verified', true)
            ->where('safety_verified', true)
            ->distinct('property_id')
            ->count('property_id');

        $avgMinutes = $completedThisWeek
            ->filter(fn ($v) => $v->started_at && $v->completed_at)
            ->map(fn ($v) => $v->started_at->diffInMinutes($v->completed_at))
            ->avg();

        $performance = [
            'week_completed' => $completedThisWeek->count(),
            'month_completed' => $completedThisMonth,
            'properties_verified' => $propertiesVerifiedTotal,
            'avg_minutes_per_visit' => $avgMinutes ? (int) round($avgMinutes) : null,
        ];

        return view('field.dashboard', compact('todayVisits', 'missedVisits', 'upcomingVisits', 'stats', 'performance'));
    }
}
