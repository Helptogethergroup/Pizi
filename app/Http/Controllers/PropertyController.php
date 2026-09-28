<?php

namespace App\Http\Controllers;

use App\Models\Amenity;
use App\Models\City;
use App\Models\Locality;
use App\Models\Property;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    /**
     * Generic search page (with filters).
     * URL: /search
     */
   public function search(Request $request)
    {
        $q = Property::active()
            ->with(['city', 'locality', 'amenities', 'images']);

        // Free-text search across name, locality, city, address
        if ($request->filled('q')) {
            $term = '%' . $request->q . '%';
            $q->where(function ($w) use ($term) {
                $w->where('name', 'like', $term)
                  ->orWhere('address_line', 'like', $term)
                  ->orWhere('landmark', 'like', $term)
                  ->orWhereHas('locality', fn($l) => $l->where('name', 'like', $term))
                  ->orWhereHas('city', fn($c) => $c->where('name', 'like', $term));
            });
        }

        // City filter
        if ($request->filled('city')) {
            $q->whereHas('city', fn($c) => $c->where('slug', $request->city));
        }

        // Locality filter
        if ($request->filled('locality')) {
            $q->whereHas('locality', fn($l) => $l->where('slug', $request->locality));
        }

        // Gender filter
        if ($request->filled('gender')) {
            $q->where(function ($w) use ($request) {
                $w->where('gender', $request->gender)
                  ->orWhere('gender', 'unisex');
            });
        }

        // Property type
        if ($request->filled('type')) {
            $q->where('property_type', $request->type);
        }

        // Budget
        if ($request->filled('budget_max')) {
            $q->where('rent_min', '<=', $request->budget_max);
        }
        if ($request->filled('budget_min')) {
            $q->where('rent_max', '>=', $request->budget_min);
        }

        // Food included
        if ($request->filled('food')) {
            $q->where('food_included', (bool) $request->food);
        }

        // Amenities (multi-select)
        if ($request->filled('amenities')) {
            $amenityIds = (array) $request->amenities;
            $q->whereHas('amenities', function ($a) use ($amenityIds) {
                $a->whereIn('amenities.id', $amenityIds);
            }, '=', count($amenityIds));
        }

        // Room type / sharing — sharing_options is a JSON object like
        // {"single": 9000, "double": 7000}, so "has this sharing type" just
        // means that key is present with a real value.
        if ($request->filled('sharing') && in_array($request->sharing, ['single', 'double', 'triple'])) {
            $q->whereNotNull('sharing_options->' . $request->sharing);
        }

        // Sort
        if ($request->filled('nearby_university_id')) {
            $q->where('nearby_university_id', (int) $request->nearby_university_id);
        }

        // Lightweight JSON modes used by the homepage (live result count, PGs near me).
        if ($request->query('json') === 'count') {
            return response()->json(['count' => (clone $q)->count()]);
        }
        if ($request->query('json') === 'nearby') {
            return $this->nearbyJson($request);
        }
        $sort = $request->get('sort', 'latest');
        if ($sort === 'nearest' && $request->filled('lat') && $request->filled('lng')) {
            $q->whereNotNull('latitude')->whereNotNull('longitude')
              ->orderByRaw($this->distanceSql(), [(float) $request->lat, (float) $request->lng, (float) $request->lat]);
        }
        match ($sort) {
            'price_low' => $q->orderBy('rent_min', 'asc'),
            'price_high' => $q->orderBy('rent_min', 'desc'),
            'popular' => $q->orderBy('view_count', 'desc'),
            default => $q->latest(),
        };

        $properties = $q->paginate(12)->withQueryString();

        // For filter sidebar
        $cities = \App\Models\City::where('is_active', true)->orderBy('name')->get();
        $amenities = \App\Models\Amenity::orderBy('name')->get();

        return view('public.search', compact('properties', 'cities', 'amenities'));
    }

    /**
     * Lightweight autocomplete suggestions — called via JS as the user
     * types in the search box. Returns a small, fast JSON list (not a
     * full page load).
     * URL: /search/suggestions?q=...
     */
    public function suggest(Request $request)
    {
        $term = trim($request->get('q', ''));

        if (strlen($term) < 2) {
            return response()->json([]);
        }

        $properties = Property::active()
            ->where('name', 'like', '%' . $term . '%')
            ->with('locality', 'city')
            ->orderByDesc('is_featured')
            ->limit(8)
            ->get(['id', 'name', 'slug', 'locality_id', 'city_id', 'rent_min']);

        $results = $properties->map(function ($p) {
            return [
                'name' => $p->name,
                'url' => url('/pg/' . $p->slug),
                'locality' => $p->locality?->name,
                'city' => $p->city?->name,
                'rent_min' => $p->rent_min,
            ];
        });

        return response()->json($results);
    }

    /**
     * SEO-friendly city page.
     * URL: /pg-in-{city_slug}
     */
    public function city(string $slug, Request $request)
    {
        $city = City::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $request->merge(['city' => $city->slug]);
        $properties = $this->filteredQuery($request)->paginate(12)->withQueryString();
     $localities = Locality::where('city_id', $city->id)
    ->where('is_active', true)
    ->withCount(['properties' => fn ($q) => $q->where('properties.is_active', true)])
    ->orderBy('name')->get();
        return view('public.city', compact('city', 'properties', 'localities'));
    }

    /**
     * SEO-friendly locality page.
     * URL: /pg-in-{city_slug}/{locality_slug}
     */
    public function locality(string $citySlug, string $localitySlug, Request $request)
    {
        $city = City::where('slug', $citySlug)->firstOrFail();
        $locality = Locality::where('city_id', $city->id)
            ->where('slug', $localitySlug)
            ->where('is_active', true)
            ->firstOrFail();

        $request->merge([
            'city' => $city->slug,
            'locality' => $locality->slug,
        ]);

        $properties = $this->filteredQuery($request)->paginate(12)->withQueryString();

        return view('public.locality', compact('city', 'locality', 'properties'));
    }

    /**
     * Property detail page.
     * URL: /pg/{slug}
     */
    public function show(string $slug)
    {
        $property = Property::where('slug', $slug)
            ->active()
            ->with(['city', 'locality', 'amenities', 'images', 'reviews', 'owner', 'landmarks'])
            ->firstOrFail();

        $property->increment('view_count');

        $similar = Property::active()
            ->where('id', '!=', $property->id)
            ->where('locality_id', $property->locality_id)
            ->with(['city', 'locality'])
            ->take(4)->get();

        if ($similar->count() < 4) {
            $more = Property::active()
                ->where('id', '!=', $property->id)
                ->where('city_id', $property->city_id)
                ->whereNotIn('id', $similar->pluck('id'))
                ->take(4 - $similar->count())->get();
            $similar = $similar->merge($more);
        }

        return view('public.property-detail', compact('property', 'similar'));
    }

    private function filteredQuery(Request $request)
{
    $q = Property::active()->with(['city', 'locality']);

        if ($request->filled('q')) {
            $term = '%' . $request->q . '%';
            $q->where(function ($qb) use ($term) {
                $qb->where('name', 'like', $term)
                   ->orWhere('address_line', 'like', $term)
                   ->orWhere('landmark', 'like', $term);
            });
        }

        if ($request->filled('city')) {
            $q->whereHas('city', fn ($c) => $c->where('slug', $request->city));
        }

        if ($request->filled('locality')) {
            $q->whereHas('locality', fn ($l) => $l->where('slug', $request->locality));
        }

        if ($request->filled('gender') && $request->gender !== 'any') {
            $q->where(function ($qb) use ($request) {
                $qb->where('gender', $request->gender)
                   ->orWhere('gender', 'unisex');
            });
        }

        if ($request->filled('min_rent')) {
            $q->where('rent_max', '>=', (float) $request->min_rent);
        }
        if ($request->filled('max_rent')) {
            $q->where('rent_min', '<=', (float) $request->max_rent);
        }

        if ($request->filled('amenities')) {
            $ids = is_array($request->amenities) ? $request->amenities : [$request->amenities];
            foreach ($ids as $aid) {
                $q->whereHas('amenities', fn ($a) => $a->where('amenities.id', $aid));
            }
        }

        if ($request->filled('sharing') && in_array($request->sharing, ['single', 'double', 'triple'])) {
            $q->whereNotNull('sharing_options->' . $request->sharing);
        }

        $sort = $request->get('sort', 'recommended');
        match ($sort) {
            'low_to_high' => $q->orderBy('rent_min', 'asc'),
            'high_to_low' => $q->orderBy('rent_max', 'desc'),
            'newest' => $q->latest(),
            default => $q->orderByDesc('is_featured')->orderByDesc('view_count'),
        };

        return $q;
    }

    /**
     * Sitemap XML for SEO.
     * URL: /sitemap.xml
     */
    public function sitemap()
    {
        $cities = City::where('is_active', true)->get();
        $localities = Locality::where('is_active', true)->with('city')->get();
        $properties = Property::active()->select('slug', 'updated_at')->get();
        $blogs = \App\Models\Blog::published()->select('slug', 'updated_at')->get();
        $landmarks = \App\Models\Landmark::where('is_active', true)->select('slug', 'updated_at')->get();

        return response()
            ->view('public.sitemap', compact('cities', 'localities', 'properties', 'blogs','landmarks'))
            ->header('Content-Type', 'application/xml');
    }
    

    /**
     * Nearest verified PGs to a coordinate — JSON for the homepage "PGs near you" block.
     */
    private function nearbyJson(Request $request)
    {
        $lat = (float) $request->query('lat');
        $lng = (float) $request->query('lng');
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180 || ($lat == 0 && $lng == 0)) {
            return response()->json(['data' => []], 422);
        }

        $rows = Property::active()
            ->where('is_verified', true)
            ->whereNotNull('latitude')->whereNotNull('longitude')
            ->where('latitude', '!=', 0)
            ->with(['city', 'locality'])
            ->selectRaw('properties.*, ' . $this->distanceSql() . ' as distance_km', [$lat, $lng, $lat])
            ->orderBy('distance_km')
            ->limit(6)
            ->get();

        // A pin is "approximate" when several PGs share the exact same point or when
        // it only has 2 decimals (~1 km precision) — usually a rough city/area pin.
        $shared = Property::active()->where('is_verified', true)
            ->whereNotNull('latitude')->whereNotNull('longitude')
            ->selectRaw('ROUND(latitude, 4) as la, ROUND(longitude, 4) as lo, COUNT(*) as c')
            ->groupBy('la', 'lo')->having('c', '>', 1)->get()
            ->mapWithKeys(fn ($r) => [$r->la . ',' . $r->lo => true]);

        return response()->json(['data' => $rows->map(fn ($p) => [
            'approx' => isset($shared[round((float) $p->latitude, 4) . ',' . round((float) $p->longitude, 4)])
                || round((float) $p->latitude, 2) == (float) $p->latitude,
            'lat' => (float) $p->latitude,
            'lng' => (float) $p->longitude,
            'name' => $p->name,
            'url' => route('property.show', $p->slug),
            'image' => $p->cover_image
                ? (str_starts_with($p->cover_image, 'http') ? $p->cover_image : asset('storage/' . $p->cover_image))
                : null,
            'locality' => $p->locality?->name,
            'city' => $p->city?->name,
            'gender' => $p->gender,
            'rent_min' => $p->rent_min,
            'distance_km' => round((float) $p->distance_km, 1),
        ])->values()]);
    }

    /** Haversine distance in km; bindings are [lat, lng, lat]. */
    private function distanceSql(): string
    {
        return '(6371 * acos(LEAST(1, cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))))';
    }
}
