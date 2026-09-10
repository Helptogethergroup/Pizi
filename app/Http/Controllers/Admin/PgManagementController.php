<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\RentBill;
use App\Models\RentPayment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;

class PgManagementController extends Controller
{
    public function index()
    {
        $stats = [
            // Tenants
            'total_tenants' => Tenant::count(),
            'active_tenants' => Tenant::where('status', 'active')->count(),
            'pending_kyc' => Tenant::whereIn('kyc_status', ['pending', 'submitted'])->count(),
            
            // Revenue
            'collected_month' => RentPayment::whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year)
                ->sum('amount'),
            'pending_dues' => RentBill::where('status', '!=', 'paid')->sum('due_amount'),
            'overdue_bills' => RentBill::where('status', 'overdue')->count(),
            
            // Complaints
            'open_complaints' => Complaint::where('status', 'open')->count(),
            'urgent_complaints' => Complaint::where('priority', 'urgent')
                ->whereNotIn('status', ['resolved', 'closed', 'cancelled'])->count(),
            'resolved_month' => Complaint::where('status', 'resolved')
                ->whereMonth('resolved_at', now()->month)->count(),
        ];

        // Top owners by tenants
        $topOwners = User::whereIn('role', ['owner', 'admin'])
            ->withCount('tenants')
            ->orderByDesc('tenants_count')
            ->take(5)
            ->get();

        // Recent activities
        $recentTenants = Tenant::with('property', 'owner')->latest()->take(5)->get();
        $recentBills = RentBill::with('tenant', 'owner')->latest()->take(5)->get();
        $urgentComplaints = Complaint::with('tenant', 'owner')
            ->where('priority', 'urgent')
            ->whereNotIn('status', ['resolved', 'closed'])
            ->latest()
            ->take(5)
            ->get();

        return view('admin.pg-management.index', compact('stats', 'topOwners', 'recentTenants', 'recentBills', 'urgentComplaints'));
    }
}