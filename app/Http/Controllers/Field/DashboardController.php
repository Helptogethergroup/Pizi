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

        return view('field.dashboard', compact('todayVisits', 'upcomingVisits', 'stats'));
    }
}