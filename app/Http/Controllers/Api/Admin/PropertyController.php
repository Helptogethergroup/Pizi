<?php

namespace App\Http\Controllers\Api\Admin;

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
            ->select('p.*', 'c.name as city_name', 'l.name as locality_name', 'u.name as owner_name', 'u.phone as owner_phone')
            ->whereNull('p.deleted_at');

        if ($request->q) $query->where('p.name', 'like', '%' . $request->q . '%');
        if ($request->verified !== null && $request->verified !== '') $query->where('p.is_verified', $request->verified == '1');
        if ($request->city_id) $query->where('p.city_id', $request->city_id);

        return response()->json(['success' => true, 'data' => $query->orderBy('p.created_at', 'desc')->get()]);
    }

    public function show($id)
    {
        $property = DB::table('properties')->where('id', $id)->first();
        if (!$property) return response()->json(['success' => false, 'message' => 'Not found'], 404);
        $property->images = DB::table('property_images')->where('property_id', $id)->get();
        $property->amenities = DB::table('property_amenities as pa')->join('amenities as a', 'pa.amenity_id', '=', 'a.id')->where('pa.property_id', $id)->get();
        return response()->json(['success' => true, 'data' => $property]);
    }

    public function verify(Request $request, $id)
    {
        DB::table('properties')->where('id', $id)->update(['is_verified' => $request->verified ?? true, 'updated_at' => now()]);
        return response()->json(['success' => true]);
    }

    public function feature(Request $request, $id)
    {
        DB::table('properties')->where('id', $id)->update(['is_featured' => $request->featured ?? true, 'updated_at' => now()]);
        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        DB::table('properties')->where('id', $id)->update(['deleted_at' => now()]);
        return response()->json(['success' => true]);
    }
}
