<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\SeoSetting;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'pages' => SeoSetting::count(),
            'active_pages' => SeoSetting::where('is_active', true)->count(),
            'total_blogs' => Blog::where('author_id', auth()->id())->count(),
            'published_blogs' => Blog::where('author_id', auth()->id())->where('is_published', true)->count(),
            'total_views' => Blog::where('author_id', auth()->id())->sum('view_count'),
        ];

        $recentBlogs = Blog::where('author_id', auth()->id())
            ->latest()->take(5)->get();

        $recentSeo = SeoSetting::with('updatedBy')
            ->latest('updated_at')->take(5)->get();

        return view('seo.dashboard', compact('stats', 'recentBlogs', 'recentSeo'));
    }
}