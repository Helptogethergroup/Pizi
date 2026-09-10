<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $ownerId = $request->user()->id;

        $totalProperties = DB::table('properties')->where('owner_id', $ownerId)->whereNull('deleted_at')->count();
        $totalTenants = DB::table('tenants')->where('owner_id', $ownerId)->where('status', 'active')->count();
        $pendingDues = DB::table('rent_bills')->where('owner_id', $ownerId)->whereIn('status', ['pending', 'partial', 'overdue'])->sum('due_amount');
        $openComplaints = DB::table('complaints')->where('owner_id', $ownerId)->whereIn('status', ['open', 'assigned', 'in_progress'])->count();
        $totalRooms = DB::table('rooms')->where('owner_id', $ownerId)->count();
        $vacantBeds = DB::table('beds')->where('owner_id', $ownerId)->where('status', 'vacant')->count();
        $occupiedBeds = DB::table('beds')->where('owner_id', $ownerId)->where('status', 'occupied')->count();

        // Current month collection
        $currentMonth = date('Y-m');
        $monthCollection = DB::table('rent_payments')
            ->where('owner_id', $ownerId)
            ->whereRaw("DATE_FORMAT(paid_at, '%Y-%m') = ?", [$currentMonth])
            ->sum('amount');

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'total_properties' => $totalProperties,
                    'total_tenants' => $totalTenants,
                    'pending_dues' => (float) $pendingDues,
                    'open_complaints' => $openComplaints,
                    'total_rooms' => $totalRooms,
                    'vacant_beds' => $vacantBeds,
                    'occupied_beds' => $occupiedBeds,
                    'month_collection' => (float) $monthCollection,
                ]
            ]
        ]);
    }
}
