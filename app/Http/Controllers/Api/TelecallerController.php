<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TelecallerController extends Controller
{
    public function dashboard(Request $request)
    {
        $tcId = $request->user->id;
        $today = now()->toDateString();

        $stats = [
            'total_assigned' => DB::table('leads')->where('assigned_to', $tcId)->count(),
            'new_leads' => DB::table('leads')->where('assigned_to', $tcId)->where('status', 'new')->count(),
            'followups_today' => DB::table('leads')->where('assigned_to', $tcId)
                ->whereDate('next_followup_at', $today)->count(),
            'closed_won' => DB::table('leads')->where('assigned_to', $tcId)->where('status', 'closed_won')->count(),
        ];

        $newLeads = DB::table('leads')
            ->leftJoin('properties', 'leads.property_id', '=', 'properties.id')
            ->where('leads.assigned_to', $tcId)
            ->where('leads.status', 'new')
            ->select('leads.*', 'properties.name as property_name')
            ->orderBy('leads.created_at', 'desc')
            ->limit(10)
            ->get();

        $followupsToday = DB::table('leads')
            ->leftJoin('properties', 'leads.property_id', '=', 'properties.id')
            ->where('leads.assigned_to', $tcId)
            ->whereDate('leads.next_followup_at', $today)
            ->select('leads.*', 'properties.name as property_name')
            ->limit(10)
            ->get();

        return $this->ok([
            'stats' => $stats,
            'new_leads' => $newLeads,
            'followups_today' => $followupsToday,
        ]);
    }

    public function leads(Request $request)
    {
        $tcId = $request->user->id;
        $q = DB::table('leads')
            ->leftJoin('properties', 'leads.property_id', '=', 'properties.id')
            ->where('leads.assigned_to', $tcId)
            ->select('leads.*', 'properties.name as property_name');

        if ($s = $request->q) $q->where(fn($w) => $w->where('leads.name', 'like', "%$s%")->orWhere('leads.phone', 'like', "%$s%"));
        if ($status = $request->status) $q->where('leads.status', $status);

        return $this->ok($q->orderBy('leads.created_at', 'desc')->limit(100)->get());
    }

    public function leadShow(Request $request, $id)
    {
        $lead = DB::table('leads')
            ->leftJoin('properties', 'leads.property_id', '=', 'properties.id')
            ->where('leads.id', $id)
            ->select('leads.*', 'properties.name as property_name')
            ->first();
        return $lead ? $this->ok($lead) : $this->notFound();
    }

    public function leadUpdate(Request $request, $id)
    {
        $data = $request->only(['status', 'notes', 'next_followup_at', 'preferred_city', 'preferred_locality',
            'preferred_gender', 'move_in_date', 'budget_min', 'budget_max']);
        $data['updated_at'] = now();
        DB::table('leads')->where('id', $id)->update($data);
        return $this->ok(['message' => 'Saved']);
    }

    public function matchingProperties(Request $request, $id)
    {
        $lead = DB::table('leads')->where('id', $id)->first();
        if (!$lead) return $this->notFound();

        $q = DB::table('properties')->whereNull('deleted_at')->where('is_active', 1);
        if ($lead->preferred_gender) $q->where('gender', $lead->preferred_gender);
        if ($lead->budget_max) $q->where('rent_min', '<=', $lead->budget_max);
        if ($lead->budget_min) $q->where('rent_max', '>=', $lead->budget_min);

        $items = $q->leftJoin('cities', 'properties.city_id', '=', 'cities.id')
            ->leftJoin('localities', 'properties.locality_id', '=', 'localities.id')
            ->select('properties.*', 'cities.name as city_name', 'localities.name as locality_name')
            ->limit(20)
            ->get();

        return $this->ok($items);
    }

    public function scheduleVisit(Request $request, $id)
    {
        DB::table('visits')->insert([
            'lead_id' => $id,
            'property_id' => $request->property_id,
            'field_executive_id' => $request->field_executive_id,
            'scheduled_at' => $request->scheduled_at,
            'status' => 'scheduled',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('leads')->where('id', $id)->update([
            'status' => 'visit_scheduled',
            'updated_at' => now(),
        ]);

        return $this->ok(['message' => 'Visit scheduled']);
    }

    private function ok($data) { return response()->json(['success' => true, 'data' => $data]); }
    private function notFound() { return response()->json(['success' => false, 'message' => 'Not found'], 404); }
}
