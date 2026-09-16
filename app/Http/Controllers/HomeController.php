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
 $cities = \App\Models\City::where('is_active', true)
    ->withCount(['properties' => fn ($q) => $q->where('properties.is_active', true)->where('properties.is_verified', true)])
    ->orderBy('name')
    ->get();

    // Total active cities we operate in — matches the "Cities" nav dropdown,
    // not just cities that happen to have a verified listing right now
    // (e.g. Ghaziabad still counts even before its first listing goes live).
 $citiesWithListings = \App\Models\City::where('cities.is_active', true)->count();

// Price-diverse featured selection — har budget-band se ek property uthao
    $priceBands = [
        [0, 8000],
        [8001, 12000],
        [12001, 18000],
        [18001, 25000],
        [25001, 999999],
    ];

    $featured = collect();
    foreach ($priceBands as [$min, $max]) {
      $pick = \App\Models\Property::where('is_active', true)
    ->where('is_verified', true)
    ->where('is_featured', true)
    ->whereBetween('rent_min', [$min, $max])
            ->whereNotIn('id', $featured->pluck('id'))
            ->with(['city', 'locality', 'amenities'])
            ->latest()
            ->first();
        if ($pick) {
            $featured->push($pick);
        }
    }

    // Agar 5 se kam mile (kisi band mein koi property na ho), baaki latest se bhar do
    if ($featured->count() < 6) {
      $extra = \App\Models\Property::where('is_active', true)
    ->where('is_verified', true)
    ->where('is_featured', true)
    ->whereNotIn('id', $featured->pluck('id'))
            ->with(['city', 'locality', 'amenities'])
            ->latest()
            ->take(6 - $featured->count())
            ->get();
        $featured = $featured->merge($extra);
    }
    
        // All-amenity PGs — properties that have EVERY amenity listed
    $totalAmenities = \App\Models\Amenity::count();

    $allAmenityProperties = \App\Models\Property::where('is_active', true)
        ->where('is_verified', true)
        ->withCount('amenities')
        ->having('amenities_count', '>=', $totalAmenities)
        ->with(['city', 'locality', 'amenities'])
        ->latest()
        ->take(6)
        ->get();
        
        
    // Low budget PGs — under ₹8,000/month
    $lowBudgetProperties = \App\Models\Property::where('is_active', true)
        ->where('is_verified', true)
        ->where('rent_min', '<=', 8000)
        ->with(['city', 'locality', 'amenities'])
        ->orderBy('rent_min', 'asc')
        ->take(6)
        ->get();
        
        
    // Verified PGs — physically inspected & trust-badged
    $verifiedProperties = \App\Models\Property::where('is_active', true)
        ->where('is_verified', true)
        ->with(['city', 'locality', 'amenities'])
        ->latest()
        ->take(6)
        ->get();
    
    $recentBlogs = \App\Models\Blog::where('is_published', true)
        ->latest('published_at')
        ->take(3)
        ->get();

    // Recently added PGs — freshest listings, creates urgency/FOMO
    $recentProperties = \App\Models\Property::where('is_active', true)
        ->where('is_verified', true)
        ->with(['city', 'locality', 'amenities'])
        ->latest()
        ->take(8)
        ->get();

    // Real tenant testimonials — only approved reviews with an actual comment
    $testimonials = \App\Models\Review::approved()
        ->whereNotNull('comment')
        ->where('comment', '!=', '')
        ->with('property:id,name,city_id', 'property.city:id,name')
        ->latest()
        ->take(8)
        ->get();

    $stats = [
       'properties' => \App\Models\Property::where('is_active', true)->count(),
        'cities' => $citiesWithListings,
        'tenants' => number_format(\App\Models\Lead::count()),
        'owners' => \App\Models\Property::where('is_active', true)->distinct('owner_id')->count('owner_id'),
    ];

                return view('public.home', compact('cities', 'featured', 'recentBlogs', 'stats', 'allAmenityProperties', 'lowBudgetProperties', 'verifiedProperties', 'recentProperties', 'testimonials'));
}

    public function about()
    {
        return view('public.about');
    }

    public function contact()
    {
        $cities = \App\Models\City::orderBy('name')->get();
        return view('public.contact', compact('cities'));
    }

    public function contactSubmit(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'phone' => 'required|string|max:15',
            'email' => 'nullable|email|max:160',
            'message' => 'required|string|max:1000',
            'inquiry_type' => 'nullable|in:tenant,owner',
            // Required for tenants — this is what lets the lead actually
            // reach the right owner instead of landing in "city unknown".
            'preferred_city' => 'required_if:inquiry_type,tenant|nullable|string|max:120',
        ]);

        // Save as a generic lead
        \App\Models\Lead::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'message' => $data['message'],
            'preferred_city' => $data['preferred_city'] ?? null,
            'source' => 'website',
            // Default to tenant, not unknown — an "unknown" lead never
            // reaches any owner's dashboard, and the vast majority of
            // contact-form submissions are someone looking for a PG.
            'inquiry_type' => $data['inquiry_type'] ?? 'tenant',
            'status' => 'new',
        ]);

        return back()->with('success', 'Thanks! Our team will reach out within 30 minutes.');
    }

    public function newsletterSubscribe(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|max:160',
        ]);

        // Avoid duplicate newsletter leads for the same email
        \App\Models\Lead::firstOrCreate(
            ['email' => $data['email'], 'source' => 'newsletter'],
            ['name' => 'Newsletter subscriber', 'phone' => '', 'status' => 'new']
        );

        return back()->with('success', 'Subscribed! Check your inbox for PG tips soon.');
    }
}
