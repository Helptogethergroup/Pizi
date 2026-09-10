<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WishlistController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $items = DB::table('wishlists as w')
            ->join('properties as p', 'w.property_id', '=', 'p.id')
            ->leftJoin('cities as c', 'p.city_id', '=', 'c.id')
            ->leftJoin('localities as l', 'p.locality_id', '=', 'l.id')
            ->where('w.user_id', $userId)
            ->select('p.*', 'c.name as city_name', 'l.name as locality_name', 'w.created_at as wishlisted_at')
            ->orderBy('w.created_at', 'desc')
            ->get();

        return response()->json(['success' => true, 'data' => $items]);
    }

    public function toggle(Request $request, $propertyId)
    {
        $userId = $request->user()->id;
        $existing = DB::table('wishlists')->where('user_id', $userId)->where('property_id', $propertyId)->first();

        if ($existing) {
            DB::table('wishlists')->where('id', $existing->id)->delete();
            return response()->json(['success' => true, 'data' => ['wishlisted' => false]]);
        }

        DB::table('wishlists')->insert([
            'user_id' => $userId,
            'property_id' => $propertyId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'data' => ['wishlisted' => true]]);
    }
}
