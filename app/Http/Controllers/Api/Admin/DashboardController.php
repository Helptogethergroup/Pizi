<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'total_properties' => DB::table('properties')->whereNull('deleted_at')->count(),
                    'verified_properties' => DB::table('properties')->where('is_verified', true)->whereNull('deleted_at')->count(),
                    'total_owners' => DB::table('users')->where('role', 'owner')->count(),
                    'total_tenants' => DB::table('tenants')->count(),
                    'total_users' => DB::table('users')->count(),
                    'total_leads' => DB::table('leads')->count(),
                    'new_leads' => DB::table('leads')->where('status', 'new')->count(),
                    'open_complaints' => DB::table('complaints')->whereIn('status', ['open', 'assigned', 'in_progress'])->count(),
                    'total_revenue' => (float) DB::table('rent_payments')->sum('amount'),
                    'month_revenue' => (float) DB::table('rent_payments')->whereRaw("DATE_FORMAT(paid_at, '%Y-%m') = ?", [date('Y-m')])->sum('amount'),
                ],
                'recent_leads' => DB::table('leads')->orderBy('created_at', 'desc')->limit(5)->get(),
                'recent_properties' => DB::table('properties')->whereNull('deleted_at')->orderBy('created_at', 'desc')->limit(5)->get(),
            ]
        ]);
    }
}
