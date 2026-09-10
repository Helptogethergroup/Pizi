<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Models\SeoSetting;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_pages' => SeoSetting::count(),
            'active_pages' => SeoSetting::where('is_active', true)->count(),
            'missing_meta' => SeoSetting::whereNull('meta_description')->orWhereNull('meta_title')->count(),
        ];

        $recentSettings = SeoSetting::latest('updated_at')->take(5)->get();

        return view('seo.dashboard', compact('stats', 'recentSettings'));
    }
}