<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;

class AnalyticsController extends Controller
{
    public function index(AnalyticsService $analytics)
    {
        $data = $analytics->ownerAnalytics(auth()->user());
        return view('owner.analytics', $data);
    }
}