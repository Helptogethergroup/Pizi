<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class PublicController extends Controller
{
    public function home()
    {
        try {
            $stats = [
                'properties' => DB::table('properties')->whereNull('deleted_at')->where('is_active', 1)->count(),
                'cities' => DB::table('cities')->count(),
                'tenants' => Schema::hasTable('tenants') ? DB::table('tenants')->count() : 0,
            ];

            if (!$stats['tenants']) $stats['tenants'] = '12,000';

            $featured = DB::table('properties')
                ->leftJoin('cities', 'properties.city_id', '=', 'cities.id')
                ->leftJoin('localities', 'properties.locality_id', '=', 'localities.id')
                ->where('properties.is_active', 1)
                ->whereNull('properties.deleted_at')
                ->where('properties.is_featured', 1)
                ->select('properties.*', 'cities.name as city_name', 'localities.name as locality_name')
                ->limit(6)
                ->get();

            if ($featured->isEmpty()) {
                $featured = DB::table('properties')
                    ->leftJoin('cities', 'properties.city_id', '=', 'cities.id')
                    ->leftJoin('localities', 'properties.locality_id', '=', 'localities.id')
                    ->where('properties.is_active', 1)
                    ->whereNull('properties.deleted_at')
                    ->select('properties.*', 'cities.name as city_name', 'localities.name as locality_name')
                    ->orderBy('properties.created_at', 'desc')
                    ->limit(6)
                    ->get();
            }

            foreach ($featured as $p) {
                 if ($p->cover_image) {
                    $p->cover_image = Storage::url($p->cover_image);
                }
                $p->city = (object) ['name' => $p->city_name ?? ''];
                $p->locality = (object) ['name' => $p->locality_name ?? ''];
                $p->amenities = [];
                if (Schema::hasTable('property_amenities')) {
                    $p->amenities = DB::table('property_amenities')
                        ->join('amenities', 'property_amenities.amenity_id', '=', 'amenities.id')
                        ->where('property_amenities.property_id', $p->id)
                        ->select('amenities.*')
                        ->limit(6)
                        ->get();
                }
            }

            $cities = DB::table('cities')
                ->select('cities.*', DB::raw('(SELECT COUNT(*) FROM properties WHERE properties.city_id = cities.id AND properties.deleted_at IS NULL AND properties.is_active = 1) as properties_count'))
                ->limit(4)
                ->get();

            $recentBlogs = Schema::hasTable('blogs')
                ? DB::table('blogs')->where('is_published', 1)->orderBy('published_at', 'desc')->limit(3)->get()
                : collect();

            return $this->ok([
                'stats' => $stats,
                'featured' => $featured,
                'cities' => $cities,
                'recent_blogs' => $recentBlogs,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile()),
            ], 500);
        }
    }

    public function search(Request $request)
    {
        try {
            $q = DB::table('properties')
                ->leftJoin('cities', 'properties.city_id', '=', 'cities.id')
                ->leftJoin('localities', 'properties.locality_id', '=', 'localities.id')
                ->where('properties.is_active', 1)
                ->whereNull('properties.deleted_at')
                ->select('properties.*', 'cities.name as city_name', 'localities.name as locality_name');

            if ($s = $request->q) {
                $q->where(function ($w) use ($s) {
                    $w->where('properties.name', 'like', "%$s%")
                      ->orWhere('properties.address_line', 'like', "%$s%")
                      ->orWhere('localities.name', 'like', "%$s%")
                      ->orWhere('cities.name', 'like', "%$s%");
                });
            }
            if ($request->gender) $q->where('properties.gender', $request->gender);
            if ($request->property_type) $q->where('properties.property_type', $request->property_type);
            if ($request->city) $q->where('cities.slug', $request->city);
            if ($request->budget_min) $q->where('properties.rent_max', '>=', $request->budget_min);
            if ($request->budget_max) $q->where('properties.rent_min', '<=', $request->budget_max);

            $properties = $q->orderBy('properties.is_featured', 'desc')
                ->orderBy('properties.created_at', 'desc')
                ->limit(60)
                ->get();

            foreach ($properties as $p) {
                if ($p->cover_image) {
                    $p->cover_image = Storage::url($p->cover_image);
                }
                $p->city = (object) ['name' => $p->city_name ?? ''];
                $p->locality = (object) ['name' => $p->locality_name ?? ''];
                $p->amenities = Schema::hasTable('property_amenities')
                    ? DB::table('property_amenities')
                        ->join('amenities', 'property_amenities.amenity_id', '=', 'amenities.id')
                        ->where('property_amenities.property_id', $p->id)
                        ->select('amenities.*')
                        ->limit(6)
                        ->get()
                    : collect();
            }

            return $this->ok($properties);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'line' => $e->getLine()], 500);
        }
    }

    public function propertyShow($slug)
    {
        try {
            $p = DB::table('properties')
                ->leftJoin('cities', 'properties.city_id', '=', 'cities.id')
                ->leftJoin('localities', 'properties.locality_id', '=', 'localities.id')
                ->where('properties.slug', $slug)
                ->whereNull('properties.deleted_at')
                ->select('properties.*', 'cities.name as city_name', 'localities.name as locality_name')
                ->first();

            if (!$p) return $this->notFound();

            DB::table('properties')->where('id', $p->id)->increment('view_count');
             // Cover image URL
            if ($p->cover_image) {
                $p->cover_image = url(Storage::url($p->cover_image));
            }
            $p->city = (object) ['name' => $p->city_name ?? ''];
            $p->locality = (object) ['name' => $p->locality_name ?? ''];
            $p->amenities = Schema::hasTable('property_amenities')
                ? DB::table('property_amenities')
                    ->join('amenities', 'property_amenities.amenity_id', '=', 'amenities.id')
                    ->where('property_amenities.property_id', $p->id)
                    ->select('amenities.*')
                    ->get()
                : collect();
                
            $p->images = Schema::hasTable('property_images')
                ? DB::table('property_images')->where('property_id', $p->id)->orderBy('display_order')->get()
                : collect();
                  foreach ($p->images as $image) {
                if ($image->image_path) {
                    $image->image_path = url(Storage::url($image->image_path));
                }
            }
            if (is_string($p->sharing_options)) $p->sharing_options = json_decode($p->sharing_options, true);

            return $this->ok($p);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function cityShow($slug)
    {
        try {
            $city = DB::table('cities')->where('slug', $slug)->first();
            if (!$city) return $this->notFound();

            $properties = DB::table('properties')
                ->leftJoin('localities', 'properties.locality_id', '=', 'localities.id')
                ->where('properties.city_id', $city->id)
                ->where('properties.is_active', 1)
                ->whereNull('properties.deleted_at')
                ->select('properties.*', 'localities.name as locality_name')
                ->limit(50)
                ->get();

            foreach ($properties as $p) {
                 // Cover image URL
                if (!empty($p->cover_image)) {
                    $p->cover_image = url(Storage::url($p->cover_image));
                }
                // Property images
                $p->images = DB::table('property_images')
                    ->where('property_id', $p->id)
                    ->get();
                foreach ($p->images as $image) {
                    if (!empty($image->image_path)) {
                        $image->image_path = url(Storage::url($image->image_path));
                    }
                }
                $p->city = (object) ['name' => $city->name];
                $p->locality = (object) ['name' => $p->locality_name ?? ''];
                $p->amenities = Schema::hasTable('property_amenities')
                    ? DB::table('property_amenities')
                        ->join('amenities', 'property_amenities.amenity_id', '=', 'amenities.id')
                        ->where('property_amenities.property_id', $p->id)
                        ->select('amenities.*')
                        ->limit(6)
                        ->get()
                    : collect();
            }

            $localities = DB::table('localities')
                ->where('city_id', $city->id)
                ->select('localities.*', DB::raw('(SELECT COUNT(*) FROM properties WHERE properties.locality_id = localities.id AND properties.deleted_at IS NULL) as properties_count'))
                ->get();

            return $this->ok([
                'city' => $city,
                'properties' => $properties,
                'localities' => $localities,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function localityShow($slug)
    {
        try {
            $locality = DB::table('localities')
                ->leftJoin('cities', 'localities.city_id', '=', 'cities.id')
                ->where('localities.slug', $slug)
                ->select('localities.*', 'cities.name as city_name')
                ->first();
            if (!$locality) return $this->notFound();

            $locality->city = (object) ['name' => $locality->city_name];

            $properties = DB::table('properties')
                ->leftJoin('cities', 'properties.city_id', '=', 'cities.id')
                ->where('properties.locality_id', $locality->id)
                ->where('properties.is_active', 1)
                ->whereNull('properties.deleted_at')
                ->select('properties.*', 'cities.name as city_name')
                ->limit(50)
                ->get();

            foreach ($properties as $p) {
                $p->city = (object) ['name' => $p->city_name ?? ''];
                $p->locality = (object) ['name' => $locality->name];
                $p->amenities = collect();
            }

            return $this->ok(['locality' => $locality, 'properties' => $properties]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function landmarkShow($slug)
    {
        if (!Schema::hasTable('landmarks')) return $this->ok(['landmark' => null, 'properties' => []]);
        $landmark = DB::table('landmarks')->where('slug', $slug)->first();
        if (!$landmark) return $this->notFound();
        return $this->ok(['landmark' => $landmark, 'properties' => []]);
    }

    public function landmarksIndex()
    {
        if (!Schema::hasTable('landmarks')) return $this->ok([]);
        // Same set the owner web form shows — active only, alphabetical.
        return $this->ok(DB::table('landmarks')->where('is_active', true)->orderBy('name')->get());
    }

    public function blogIndex()
    {
        if (!Schema::hasTable('blogs')) return $this->ok([]);
        return $this->ok(DB::table('blogs')->where('is_published', 1)->orderBy('published_at', 'desc')->limit(50)->get());
    }

    public function blogShow($slug)
    {
        if (!Schema::hasTable('blogs')) return $this->notFound();
        $blog = DB::table('blogs')->where('slug', $slug)->where('is_published', 1)->first();
        return $blog ? $this->ok($blog) : $this->notFound();
    }

    public function sitemap()
    {
        return $this->ok([
            'cities' => DB::table('cities')->get(),
            'localities' => DB::table('localities')->limit(100)->get(),
            'properties' => DB::table('properties')->where('is_active', 1)->whereNull('deleted_at')->select('id', 'name', 'slug')->limit(200)->get(),
        ]);
    }

    public function contact(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'message' => 'required|string',
            'inquiry_type' => 'nullable|in:tenant,owner',
            // Required for tenants — this is what lets the lead reach the
            // right owner instead of landing in the "city unknown" bucket.
            'preferred_city' => 'required_if:inquiry_type,tenant|nullable|string|max:120',
        ]);

        DB::table('leads')->insert([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone ?? '',
            'message' => $request->message,
            'preferred_city' => $request->preferred_city,
            'source' => 'contact_form',
            'inquiry_type' => in_array($request->inquiry_type, ['tenant', 'owner']) ? $request->inquiry_type : 'unknown',
            'status' => 'new',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->ok(['message' => 'Thanks! We will get back soon.']);
    }

    public function leadStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'phone' => 'required|string',
        ]);

        $leadId = DB::table('leads')->insertGetId([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'message' => $request->message,
            'property_id' => $request->property_id,
            'source' => $request->source ?? 'website',
            'status' => 'new',
            'credit_cost' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->ok(['lead_id' => $leadId, 'message' => 'Got it! We will call you soon.']);
    }

    public function cities() { return $this->ok(DB::table('cities')->orderBy('name')->get()); }

    /**
     * Optional ?city_id=X filter — without it, returns all localities
     * (kept for backward compatibility with any existing callers).
     */
    public function localities(Request $request)
    {
        $query = DB::table('localities')->orderBy('name');

        if ($request->filled('city_id')) {
            $query->where('city_id', $request->city_id);
        }

        return $this->ok($query->get());
    }
    public function amenities() { return $this->ok(DB::table('amenities')->orderBy('name')->get()); }

    /**
     * Optional ?city=CityName filter (universities table stores city as
     * plain text, not a foreign key) — without it, returns all universities.
     */
    public function universities(Request $request)
    {
        $query = DB::table('universities')->orderBy('city')->orderBy('name');

        if ($request->filled('city')) {
            $query->where('city', $request->city);
        }

        return $this->ok($query->get());
    }

    public function allProperties()
    {
        $properties = DB::table('properties')
            ->where('is_active', 1)
            ->whereNull('deleted_at')
            ->select('id', 'name', 'slug', 'rent_min', 'rent_max', 'cover_image')
            ->orderBy('name')
            ->limit(500)
            ->get();

        foreach ($properties as $property) {
            if ($property->cover_image) {
                $property->cover_image = url(Storage::url($property->cover_image));
            }
        }

        return $this->ok($properties);
    }

    private function ok($data) { return response()->json(['success' => true, 'data' => $data]); }
    private function notFound() { return response()->json(['success' => false, 'message' => 'Not found'], 404); }
}