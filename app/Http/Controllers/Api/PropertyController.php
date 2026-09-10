<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PropertyController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('properties as p')
            ->leftJoin('cities as c', 'p.city_id', '=', 'c.id')
            ->leftJoin('localities as l', 'p.locality_id', '=', 'l.id')
            ->leftJoin('users as u', 'p.owner_id', '=', 'u.id')
            ->select(
                'p.*',
                'c.name as city_name',
                'c.slug as city_slug',
                'l.name as locality_name',
                'l.slug as locality_slug',
                'u.name as owner_name'
            )
            ->where('p.is_active', true)
            ->whereNull('p.deleted_at');

        // Filters
        if ($request->q) {
            $query->where('p.name', 'like', '%' . $request->q . '%');
        }
        if ($request->city) {
            $query->where('c.slug', $request->city);
        }
        if ($request->locality) {
            $query->where('l.slug', $request->locality);
        }
        if ($request->gender) {
            $query->where('p.gender', $request->gender);
        }
        if ($request->property_type) {
            $query->where('p.property_type', $request->property_type);
        }
        if ($request->is_featured) {
            $query->where('p.is_featured', true);
        }
        if ($request->is_verified) {
            $query->where('p.is_verified', true);
        }
        if ($request->min_rent) {
            $query->where('p.rent_min', '>=', $request->min_rent);
        }
        if ($request->max_rent) {
            $query->where('p.rent_max', '<=', $request->max_rent);
        }

        // Sort
        $sort = $request->sort ?? 'featured';
        switch ($sort) {
            case 'price_low':
                $query->orderBy('p.rent_min', 'asc');
                break;
            case 'price_high':
                $query->orderBy('p.rent_min', 'desc');
                break;
            case 'newest':
                $query->orderBy('p.created_at', 'desc');
                break;
            case 'popular':
                $query->orderBy('p.view_count', 'desc');
                break;
            default:
                $query->orderBy('p.is_featured', 'desc')->orderBy('p.created_at', 'desc');
        }

        $page = (int) ($request->page ?? 1);
        $limit = min((int) ($request->limit ?? 20), 100);

        $total = $query->count();
        $items = $query->offset(($page - 1) * $limit)->limit($limit)->get();

        // Hydrate images for each property
        $propertyIds = $items->pluck('id')->toArray();
        $images = DB::table('property_images')
            ->whereIn('property_id', $propertyIds)
            ->orderBy('display_order')
            ->get()
            ->groupBy('property_id');

        $items = $items->map(function ($p) use ($images) {
            $p->images = $images->get($p->id, collect([]))->take(5)->values();
            $p->image_url = $p->cover_image
                ? url('storage/' . $p->cover_image)
                : ($p->images->first() ? url('storage/' . $p->images->first()->image_path) : null);
            return $p;
        });

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $items,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit),
                ]
            ]
        ]);
    }

    public function featured(Request $request)
    {
        $limit = min((int) ($request->limit ?? 6), 20);
        $request->merge(['is_featured' => true, 'limit' => $limit]);
        return $this->index($request);
    }

    public function show($slug)
    {
        $property = DB::table('properties as p')
            ->leftJoin('cities as c', 'p.city_id', '=', 'c.id')
            ->leftJoin('localities as l', 'p.locality_id', '=', 'l.id')
            ->leftJoin('users as u', 'p.owner_id', '=', 'u.id')
            ->select(
                'p.*',
                'c.id as city_id_v',
                'c.name as city_name',
                'c.slug as city_slug',
                'l.id as locality_id_v',
                'l.name as locality_name',
                'l.slug as locality_slug',
                'u.id as owner_id_v',
                'u.name as owner_name',
                'u.phone as owner_phone',
                'u.email as owner_email',
                'u.avatar as owner_avatar'
            )
            ->where(function ($q) use ($slug) {
                $q->where('p.slug', $slug)->orWhere('p.id', $slug);
            })
            ->whereNull('p.deleted_at')
            ->first();

        if (!$property) {
            return response()->json(['success' => false, 'message' => 'Property not found'], 404);
        }

        // Images
        $property->images = DB::table('property_images')
            ->where('property_id', $property->id)
            ->orderBy('display_order')
            ->get();

        // Amenities
        $property->amenities = DB::table('property_amenities as pa')
            ->join('amenities as a', 'pa.amenity_id', '=', 'a.id')
            ->where('pa.property_id', $property->id)
            ->select('a.id', 'a.name', 'a.slug', 'a.icon')
            ->get();

        // Increment view count
        DB::table('properties')->where('id', $property->id)->increment('view_count');

        return response()->json([
            'success' => true,
            'data' => $property
        ]);
    }

    public function trackView($id)
    {
        DB::table('properties')->where('id', $id)->increment('view_count');
        return response()->json(['success' => true]);
    }
}
