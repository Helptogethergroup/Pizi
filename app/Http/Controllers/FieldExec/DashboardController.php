<?php

namespace App\Http\Controllers\Field;

use App\Http\Controllers\Controller;
use App\Models\FieldVisit;
use Illuminate\Http\Request;

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
            ->whereDate('scheduled_at', '<=', today()->addDays(7))
            ->with('property.city', 'property.locality')
            ->orderBy('scheduled_at')
            ->take(10)
            ->get();

        $stats = [
            'today_count' => $todayVisits->count(),
            'today_completed' => $todayVisits->where('status', 'completed')->count(),
            'pending_total' => FieldVisit::where('field_executive_id', $userId)
                ->whereIn('status', ['scheduled', 'in_progress'])->count(),
            'completed_this_month' => FieldVisit::where('field_executive_id', $userId)
                ->where('status', 'completed')
                ->whereMonth('completed_at', now()->month)
                ->count(),
        ];

        return view('field.dashboard', compact('todayVisits', 'upcomingVisits', 'stats'));
    }
}