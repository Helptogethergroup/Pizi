<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        // Counts for the Tenant/Owner tabs — computed before any filter.
        $tenantCount = Lead::where('inquiry_type', 'tenant')->count();
        $ownerCount = Lead::where('inquiry_type', 'owner')->count();

        $q = Lead::with(['property.locality', 'property.city', 'telecaller']);

        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }
         // Lead Type Filter
        if ($request->filled('lead_type')) {
            $q->where('lead_type', $request->lead_type);
        }
        // Inquiry type (tenant looking for a PG vs. owner wanting to list one)
        if ($request->filled('inquiry_type')) {
            $q->where('inquiry_type', $request->inquiry_type);
        }
        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $q->where(function ($qb) use ($term) {
                $qb->where('name', 'like', $term)
                   ->orWhere('phone', 'like', $term)
                   ->orWhere('email', 'like', $term);
            });
        }

        $leads = $q->latest()->paginate(25)->withQueryString();
        $telecallers = User::where('role', 'telecaller')->where('is_active', true)->get();
        return view('admin.leads', compact('leads', 'telecallers', 'tenantCount', 'ownerCount'));
    }

    public function assign(Request $request, Lead $lead)
    {
        $request->validate([
            'telecaller_id' => 'required|exists:users,id',
        ]);

        // Owner ne claim kar li — assign hi nahi hone denge
        if ($lead->is_locked && $lead->locked_by_user_id) {
            $ownerUser = User::find($lead->locked_by_user_id);
            if ($ownerUser && $ownerUser->role === 'owner') {
                return back()->with('error', '🔒 This lead has been claimed by PG owner: ' . $ownerUser->name . '. Cannot assign to telecaller.');
            }
        }

        // Already assign ho chuki hai — dubara assign nahi hone denge
        if ($lead->assigned_telecaller_id) {
            $existingTc = User::find($lead->assigned_telecaller_id);
            return back()->with('error', '⚠️ Lead already assigned to ' . ($existingTc->name ?? 'a telecaller') . '.');
        }

$lead->update(['assigned_telecaller_id' => $request->telecaller_id]);
        return back()->with('success', 'Lead assigned.');
    }

    public function markVerified(Lead $lead)
    {
        $lead->update(['lead_type' => 'verified']);
        return back()->with('success', '✓ Lead marked as Verified.');
    }
    
    
    public function destroy(Lead $lead)
{
    $lead->delete();
    return back()->with('success', '🗑️ Lead deleted successfully.');
}
    


}