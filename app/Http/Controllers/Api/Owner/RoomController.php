<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RoomController extends Controller
{
    private function ownerScope(Request $request, $query, $alias = '')
    {
        if ($request->user()->role !== 'admin') {
            $col = $alias ? $alias . '.owner_id' : 'owner_id';
            $query->where($col, $request->user()->id);
        }
        return $query;
    }

    public function index(Request $request)
    {
        $query = DB::table('rooms as r')
            ->leftJoin('properties as p', 'r.property_id', '=', 'p.id')
            ->select('r.*', 'p.name as property_name');
        $this->ownerScope($request, $query, 'r');

        if ($request->property_id) $query->where('r.property_id', $request->property_id);

        $rooms = $query->orderBy('r.created_at', 'desc')->get();
        $ids = $rooms->pluck('id')->toArray();
        $beds = DB::table('beds')->whereIn('room_id', $ids)->get()->groupBy('room_id');
        $rooms = $rooms->map(function ($r) use ($beds) {
            $r->beds = $beds->get($r->id, collect([]));
            $r->total_beds = $r->beds->count();
            $r->occupied_beds = $r->beds->where('status', 'occupied')->count();
            return $r;
        });

        return response()->json(['success' => true, 'data' => $rooms]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $validator = Validator::make($request->all(), [
            'property_id' => 'required|integer|exists:properties,id',
            'room_number' => 'required|string',
            'room_type' => 'sometimes|in:single,double,triple,quad,quint,dorm',
            'monthly_rent' => 'sometimes|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $id = DB::table('rooms')->insertGetId(array_merge($request->only([
            'property_id', 'room_number', 'floor', 'room_type', 'gender',
            'monthly_rent', 'security_deposit',
            'has_ac', 'has_attached_bathroom', 'has_balcony', 'has_geyser', 'has_wifi', 'notes',
        ]), [
            'owner_id' => $user->id,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        // Auto-create beds based on room type
        $bedsCount = match ($request->room_type) {
            'single' => 1, 'double' => 2, 'triple' => 3, 'quad' => 4, 'quint' => 5, default => 1,
        };
        for ($i = 1; $i <= $bedsCount; $i++) {
            DB::table('beds')->insert([
                'room_id' => $id,
                'property_id' => $request->property_id,
                'owner_id' => $user->id,
                'bed_number' => 'B' . $i,
                'bed_type' => 'single',
                'monthly_rent' => $request->monthly_rent ?? 0,
                'status' => 'vacant',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json(['success' => true, 'data' => ['id' => $id]]);
    }

    public function show(Request $request, $id)
    {
        $query = DB::table('rooms')->where('id', $id);
        $this->ownerScope($request, $query);
        $room = $query->first();
        if (!$room) return response()->json(['success' => false, 'message' => 'Not found'], 404);

        $room->beds = DB::table('beds')->where('room_id', $id)->orderBy('bed_number')->get();
        $room->property = DB::table('properties')->where('id', $room->property_id)->first();
        return response()->json(['success' => true, 'data' => $room]);
    }

    public function update(Request $request, $id)
    {
        $query = DB::table('rooms')->where('id', $id);
        $this->ownerScope($request, $query);
        if (!$query->first()) return response()->json(['success' => false, 'message' => 'Not found'], 404);

        $data = $request->only([
            'room_number', 'floor', 'room_type', 'gender', 'monthly_rent', 'security_deposit',
            'has_ac', 'has_attached_bathroom', 'has_balcony', 'has_geyser', 'has_wifi', 'notes', 'status',
        ]);
        $data['updated_at'] = now();
        DB::table('rooms')->where('id', $id)->update($data);
        return response()->json(['success' => true]);
    }

    public function destroy(Request $request, $id)
    {
        $query = DB::table('rooms')->where('id', $id);
        $this->ownerScope($request, $query);
        if (!$query->first()) return response()->json(['success' => false, 'message' => 'Not found'], 404);
        DB::table('beds')->where('room_id', $id)->delete();
        DB::table('rooms')->where('id', $id)->delete();
        return response()->json(['success' => true]);
    }

    public function storeBed(Request $request, $roomId)
    {
        $user = $request->user();
        $query = DB::table('rooms')->where('id', $roomId);
        $this->ownerScope($request, $query);
        $room = $query->first();
        if (!$room) return response()->json(['success' => false, 'message' => 'Room not found'], 404);

        $id = DB::table('beds')->insertGetId([
            'room_id' => $roomId,
            'property_id' => $room->property_id,
            'owner_id' => $user->id,
            'bed_number' => $request->bed_number,
            'bed_type' => $request->bed_type ?? 'single',
            'monthly_rent' => $request->monthly_rent ?? $room->monthly_rent,
            'status' => 'vacant',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return response()->json(['success' => true, 'data' => ['id' => $id]]);
    }

    public function assignBed(Request $request, $bedId)
    {
        $tenantId = $request->tenant_id;
        DB::table('beds')->where('id', $bedId)->update([
            'tenant_id' => $tenantId,
            'status' => 'occupied',
            'occupied_since' => now(),
            'updated_at' => now(),
        ]);
        return response()->json(['success' => true]);
    }

    public function unassignBed($bedId)
    {
        DB::table('beds')->where('id', $bedId)->update([
            'tenant_id' => null,
            'status' => 'vacant',
            'occupied_since' => null,
            'updated_at' => now(),
        ]);
        return response()->json(['success' => true]);
    }

    public function changeBedStatus(Request $request, $bedId)
    {
        DB::table('beds')->where('id', $bedId)->update(['status' => $request->status, 'updated_at' => now()]);
        return response()->json(['success' => true]);
    }

    public function destroyBed($bedId)
    {
        DB::table('beds')->where('id', $bedId)->delete();
        return response()->json(['success' => true]);
    }
}
