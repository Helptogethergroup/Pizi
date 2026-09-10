<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LocationController extends Controller
{
    public function cities()
    {
        $cities = DB::table('cities')
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        // Add property count for each city
        $cities = $cities->map(function ($city) {
            $city->property_count = DB::table('properties')
                ->where('city_id', $city->id)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->count();
            return $city;
        });

        return response()->json(['success' => true, 'data' => $cities]);
    }

    public function cityDetail($slug)
    {
        $city = DB::table('cities')->where('slug', $slug)->first();
        if (!$city) return response()->json(['success' => false, 'message' => 'City not found'], 404);

        $city->localities = DB::table('localities')->where('city_id', $city->id)->where('is_active', true)->get();
        $city->property_count = DB::table('properties')->where('city_id', $city->id)->where('is_active', true)->whereNull('deleted_at')->count();

        return response()->json(['success' => true, 'data' => $city]);
    }

    public function localities($cityId)
    {
        $localities = DB::table('localities')
            ->where('city_id', $cityId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json(['success' => true, 'data' => $localities]);
    }

    public function localityDetail($slug)
    {
        $locality = DB::table('localities as l')
            ->leftJoin('cities as c', 'l.city_id', '=', 'c.id')
            ->where('l.slug', $slug)
            ->select('l.*', 'c.name as city_name', 'c.slug as city_slug')
            ->first();

        if (!$locality) return response()->json(['success' => false, 'message' => 'Locality not found'], 404);

        return response()->json(['success' => true, 'data' => $locality]);
    }
}
