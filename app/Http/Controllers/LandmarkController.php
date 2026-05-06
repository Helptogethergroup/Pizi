<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Landmark;
use App\Models\Property;
use Illuminate\Http\Request;

class LandmarkController extends Controller
{
    /**
     * Public landmark page.
     * URL: /pg-near-{landmark_slug}
     */
    public function show(string $slug, Request $request)
    {
        $landmark = Landmark::where('slug', $slug)
            ->where('is_active', true)
            ->with(['city', 'locality'])
            ->firstOrFail();

        $landmark->increment('view_count');

        // Properties within 5 km, sorted by distance
        $properties = Property::active()
            ->whereHas('landmarks', fn ($q) => $q->where('landmarks.id', $landmark->id))
            ->with(['city', 'locality', 'landmarks' => fn ($q) => $q->where('landmarks.id', $landmark->id)])
            ->paginate(12);

        // Other landmarks in same city
        $relatedLandmarks = Landmark::where('city_id', $landmark->city_id)
            ->where('id', '!=', $landmark->id)
            ->where('is_active', true)
            ->take(8)
            ->get();

        return view('public.landmark', compact('landmark', 'properties', 'relatedLandmarks'));
    }

    /**
     * All landmarks index page.
     * URL: /landmarks
     */
    public function index()
    {
        $landmarks = Landmark::where('is_active', true)
            ->with('city')
            ->orderBy('type')
            ->orderBy('display_order')
            ->orderBy('name')
            ->get()
            ->groupBy('type');

        return view('public.landmarks-index', compact('landmarks'));
    }
}