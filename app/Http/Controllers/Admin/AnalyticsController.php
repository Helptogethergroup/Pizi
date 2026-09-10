<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;

class AnalyticsController extends Controller
{
    public function index(AnalyticsService $analytics)
    {
        $data = [
            'revenue' => $analytics->revenueLastDays(30),
            'funnel' => $analytics->leadFunnel(),
            'sources' => $analytics->leadSources(),
            'cities' => $analytics->topCitiesByLeads(),
            'conversion' => $analytics->conversionTrend(30),
            'topSpenders' => $analytics->topSpendingOwners(),
            'topPerformers' => $analytics->topPerformingOwners(),
            'fieldExecs' => $analytics->fieldExecPerformance(),
        ];

        return view('admin.analytics', $data);
    }
}