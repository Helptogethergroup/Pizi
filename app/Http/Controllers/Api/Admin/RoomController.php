<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('rooms as r')
            ->leftJoin('properties as p', 'r.property_id', '=', 'p.id')
            ->leftJoin('users as u', 'r.owner_id', '=', 'u.id')
            ->select('r.*', 'p.name as property_name', 'u.name as owner_name');

        if ($request->property_id) $query->where('r.property_id', $request->property_id);

        $rooms = $query->orderBy('r.created_at', 'desc')->get();
        $ids = $rooms->pluck('id')->toArray();
        $beds = DB::table('beds')->whereIn('room_id', $ids)->get()->groupBy('room_id');
        $rooms = $rooms->map(function ($r) use ($beds) {
            $r->beds = $beds->get($r->id, collect([]));
            return $r;
        });

        return response()->json(['success' => true, 'data' => $rooms]);
    }
}
