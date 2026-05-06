<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\City;
use App\Models\Property;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $featured = Property::active()->featured()
            ->with(['city', 'locality', 'images'])
            ->latest()->take(6)->get();

        $latest = Property::active()
            ->with(['city', 'locality'])
            ->latest()->take(8)->get();

        $cities = City::where('is_active', true)
           ->withCount(['properties' => fn ($q) => $q->where('properties.is_active', true)])
            ->orderBy('display_order')->get();

        $stats = [
            'properties' => Property::active()->count(),
            'cities' => City::where('is_active', true)->count(),
            'happy_residents' => 12000 + (Property::active()->count() * 7),
        ];

        $blogs = Blog::published()->latest('published_at')->take(3)->get();

        return view('public.home', compact('featured', 'latest', 'cities', 'stats', 'blogs'));
    }

    public function about()
    {
        return view('public.about');
    }

    public function contact()
    {
        return view('public.contact');
    }

    public function contactSubmit(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'phone' => 'required|string|max:15',
            'email' => 'nullable|email|max:160',
            'message' => 'required|string|max:1000',
        ]);

        // Save as a generic lead
        \App\Models\Lead::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'message' => $data['message'],
            'source' => 'website',
            'status' => 'new',
        ]);

        return back()->with('success', 'Thanks! Our team will reach out within 30 minutes.');
    }
}
