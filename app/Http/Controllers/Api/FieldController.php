<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FieldController extends Controller
{
    public function dashboard(Request $request)
    {
        $execId = $request->user->id;
        $today = now()->toDateString();

        $stats = [
            'today_visits' => DB::table('visits')->where('field_executive_id', $execId)->whereDate('scheduled_at', $today)->count(),
            'today_completed' => DB::table('visits')->where('field_executive_id', $execId)->whereDate('completed_at', $today)->count(),
            'pending_total' => DB::table('visits')->where('field_executive_id', $execId)->whereIn('status', ['scheduled', 'in_progress'])->count(),
            'total_done' => DB::table('visits')->where('field_executive_id', $execId)->where('status', 'completed')->count(),
        ];

        $todaySchedule = DB::table('visits')
            ->leftJoin('properties', 'visits.property_id', '=', 'properties.id')
            ->where('visits.field_executive_id', $execId)
            ->whereDate('visits.scheduled_at', $today)
            ->select('visits.*', 'properties.name as property_name', 'properties.address_line')
            ->orderBy('visits.scheduled_at')
            ->get();

        $upcoming = DB::table('visits')
            ->leftJoin('properties', 'visits.property_id', '=', 'properties.id')
            ->where('visits.field_executive_id', $execId)
            ->where('visits.scheduled_at', '>', now())
            ->where('visits.status', 'scheduled')
            ->select('visits.*', 'properties.name as property_name', 'properties.address_line')
            ->orderBy('visits.scheduled_at')
            ->limit(10)
            ->get();

        return $this->ok([
            'stats' => $stats,
            'today_schedule' => $todaySchedule,
            'upcoming' => $upcoming,
        ]);
    }

    public function visits(Request $request)
    {
        $q = DB::table('visits')
            ->leftJoin('properties', 'visits.property_id', '=', 'properties.id')
            ->where('visits.field_executive_id', $request->user->id)
            ->select('visits.*', 'properties.name as property_name', 'properties.address_line');

        if ($status = $request->status) $q->where('visits.status', $status);

        $items = $q->orderBy('visits.scheduled_at', 'desc')->limit(100)->get();
        foreach ($items as $v) {
            $v->property = (object) ['name' => $v->property_name, 'address_line' => $v->address_line];
        }
        return $this->ok($items);
    }

    public function visitShow(Request $request, $id)
    {
        $visit = DB::table('visits')
            ->leftJoin('properties', 'visits.property_id', '=', 'properties.id')
            ->leftJoin('localities', 'properties.locality_id', '=', 'localities.id')
            ->leftJoin('cities', 'properties.city_id', '=', 'cities.id')
            ->where('visits.id', $id)
            ->where('visits.field_executive_id', $request->user->id)
            ->select(
                'visits.*',
                'properties.id as property_id_full', 'properties.name as property_name',
                'properties.address_line', 'properties.property_type', 'properties.gender',
                'properties.rent_min', 'properties.total_rooms', 'properties.google_map_link',
                'localities.name as locality_name', 'cities.name as city_name'
            )
            ->first();

        if (!$visit) return $this->notFound();

        $progressFields = ['address_verified', 'amenities_verified', 'rooms_verified', 'safety_verified'];
        $done = 0;
        foreach ($progressFields as $f) if (($visit->$f ?? 0) == 1) $done++;
        $visit->verification_progress = round(($done / count($progressFields)) * 100);

        $visit->property = (object) [
            'id' => $visit->property_id_full,
            'name' => $visit->property_name,
            'address_line' => $visit->address_line,
            'property_type' => $visit->property_type,
            'gender' => $visit->gender,
            'rent_min' => $visit->rent_min,
            'total_rooms' => $visit->total_rooms,
            'google_map_link' => $visit->google_map_link,
            'locality' => (object) ['name' => $visit->locality_name],
            'city' => (object) ['name' => $visit->city_name],
            'amenities' => DB::table('property_amenities')
                ->join('amenities', 'property_amenities.amenity_id', '=', 'amenities.id')
                ->where('property_amenities.property_id', $visit->property_id_full)
                ->select('amenities.*')
                ->get(),
        ];

        $visit->media = DB::table('visit_media')->where('visit_id', $id)->get();

        return $this->ok($visit);
    }

    public function visitStart(Request $request, $id)
    {
        DB::table('visits')->where('id', $id)->update([
            'status' => 'in_progress',
            'started_at' => now(),
            'checked_in_at' => now(),
            'checkin_lat' => $request->lat,
            'checkin_lng' => $request->lng,
            'updated_at' => now(),
        ]);
        return $this->ok(['message' => 'Started']);
    }

    public function visitComplete(Request $request, $id)
    {
        DB::table('visits')->where('id', $id)->update([
            'status' => 'completed',
            'completed_at' => now(),
            'checkout_lat' => $request->lat,
            'checkout_lng' => $request->lng,
            'updated_at' => now(),
        ]);
        return $this->ok(['message' => 'Completed']);
    }

    public function visitVerify(Request $request, $id)
    {
        DB::table('visits')->where('id', $id)->update([
            'address_verified' => $request->boolean('address_verified') ? 1 : 0,
            'amenities_verified' => $request->boolean('amenities_verified') ? 1 : 0,
            'rooms_verified' => $request->boolean('rooms_verified') ? 1 : 0,
            'safety_verified' => $request->boolean('safety_verified') ? 1 : 0,
            'remarks' => $request->remarks,
            'updated_at' => now(),
        ]);
        return $this->ok(['message' => 'Saved']);
    }

    public function visitMedia(Request $request, $id)
    {
        if ($request->hasFile('media')) {
            foreach ($request->file('media') as $file) {
                $path = $file->store('visits/' . $id, 'public');
                $type = str_contains($file->getMimeType(), 'video') ? 'video' : 'photo';
                DB::table('visit_media')->insert([
                    'visit_id' => $id,
                    'media_type' => $type,
                    'file_path' => $path,
                    'caption' => $request->caption,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        return $this->ok(['message' => 'Uploaded']);
    }

    private function ok($data) { return response()->json(['success' => true, 'data' => $data]); }
    private function notFound() { return response()->json(['success' => false, 'message' => 'Not found'], 404); }
}
