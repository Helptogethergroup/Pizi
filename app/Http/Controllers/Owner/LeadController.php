<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadPricing;
use App\Models\Property;
use App\Services\LeadMatchingService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use RuntimeException;

class LeadController extends Controller
{
    public function index(Request $request, LeadMatchingService $matcher)
    {
        $owner = auth()->user();
        $allMatched = $matcher->leadsForOwner($owner, 60);

        $tab = $request->get('tab', 'all');

        // Filter by tab (existing lead-type based tabs)
        $filtered = match ($tab) {
            'hot' => $allMatched->where('match_score', '>=', 70),
            'verified' => $allMatched->where('lead_type', 'verified'),
            'manual' => $allMatched->where('lead_type', 'manual'),
            'affordable' => $allMatched->filter(fn($l) => $l->affordable && !$l->is_unlocked),
            'unlocked' => $allMatched->where('is_unlocked', true),
            default => $allMatched,
        };

        // NEW: Status filter (only meaningful for unlocked leads)
        if ($request->filled('status')) {
            $filtered = $filtered->where('status', $request->status);
        }

        // NEW: Date filter
        if ($request->filled('date_range')) {
            $filtered = $filtered->filter(function ($l) use ($request) {
                if (!$l->created_at) return false;
                $created = \Carbon\Carbon::parse($l->created_at);
                return match ($request->date_range) {
                    'today' => $created->isToday(),
                    'yesterday' => $created->isYesterday(),
                    'week' => $created->isCurrentWeek(),
                    'month' => $created->isCurrentMonth(),
                    default => true,
                };
            });
        }

        // NEW: Assigned telecaller filter
        if ($request->filled('assigned_to')) {
            $filtered = $filtered->where('assigned_telecaller_id', $request->assigned_to);
        }

        // NEW: Property filter
        if ($request->filled('property_id')) {
            $filtered = $filtered->where('property_id', $request->property_id);
        }

        // NEW: Source filter
        if ($request->filled('source')) {
            $filtered = $filtered->where('source', $request->source);
        }

        // NEW: Inquiry type filter (tenant vs. owner enquiry)
        if ($request->filled('inquiry_type')) {
            $filtered = $filtered->where('inquiry_type', $request->inquiry_type);
        }

        $filtered = $filtered->values();

        $page = (int) $request->get('page', 1);
        $perPage = 12;
        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $filtered->forPage($page, $perPage),
            $filtered->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $wallet = $owner->wallet ?? \App\Models\Wallet::firstOrCreate(
            ['user_id' => $owner->id],
            ['balance' => 0]
        );

        // Tab counts (lead-type based, unchanged)
        $counts = [
            'all' => $allMatched->count(),
            'hot' => $allMatched->where('match_score', '>=', 70)->count(),
            'verified' => $allMatched->where('lead_type', 'verified')->count(),
            'manual' => $allMatched->where('lead_type', 'manual')->count(),
            'affordable' => $allMatched->filter(fn($l) => $l->affordable && !$l->is_unlocked)->count(),
            'unlocked' => $allMatched->where('is_unlocked', true)->count(),
        ];

        // NEW: Status counts — sirf unlocked leads pe (locked lead ka status track karne ka koi matlab nahi)
        $unlockedLeads = $allMatched->where('is_unlocked', true);
        $statusCounts = [
            'new_lead' => $unlockedLeads->filter(fn($l) => !$l->status || $l->status === 'new_lead')->count(),
            'open' => $unlockedLeads->where('status', 'open')->count(),
            'connected' => $unlockedLeads->where('status', 'connected')->count(),
            'follow_up' => $unlockedLeads->where('status', 'follow_up')->count(),
            'deal_closed' => $unlockedLeads->where('status', 'deal_closed')->count(),
            'lost' => $unlockedLeads->where('status', 'lost')->count(),
        ];

        // NEW: Dropdown data for filters
        $properties = \App\Models\Property::where('owner_id', $owner->id)->get(['id', 'name']);
        $telecallers = \App\Models\User::where('role', 'telecaller')->get(['id', 'name']);
        $sources = $allMatched->pluck('source')->filter()->unique()->values();

        // Pricing for top strip
        $pricing = \App\Models\LeadPricing::where('is_active', true)->get()->keyBy('lead_type');

        return view('owner.leads', compact(
            'paginated', 'wallet', 'tab', 'counts', 'pricing',
            'statusCounts', 'properties', 'telecallers', 'sources'
        ));
    }

public function unlock(Lead $lead, WalletService $service)
    {
        $owner = auth()->user();

        try {
            $result = $service->unlockLead($owner, $lead);
            $balance = $result['balance_remaining'] ?? $owner->wallet?->fresh()?->balance ?? 0;
            return back()->with('success', "✓ Lead unlocked! {$result['credits_spent']} credits used. Balance: {$balance}");
        } catch (RuntimeException $e) {
            return back()->withErrors(['unlock' => $e->getMessage()]);
        }
    }

    /**
     * Owner self-service junk report — a lead they never unlocked but can
     * tell at a glance is spam/test/irrelevant (e.g. "sex" as the message).
     * No unlock/credit spend needed; just hides it from every owner going
     * forward, same as the admin/telecaller "Junk status" mechanism.
     */
    public function reportJunk(Lead $lead)
    {
        if ($lead->is_unlocked) {
            return back()->withErrors(['junk' => 'This lead is already unlocked — use its status instead.']);
        }

        \DB::table('leads')->where('id', $lead->id)->update([
            'status' => 'junk',
            'updated_at' => now(),
        ]);

        return back()->with('success', '🚩 Reported as junk — it will no longer be shown to owners.');
    }

    private const VALID_STATUSES = [
        'new_lead', 'open', 'contacted', 'connected', 'not_connected',
        'follow_up', 'visit_scheduled', 'visit_completed', 'deal_closed', 'lost', 'cancelled',
    ];

    public function updateStatus(Request $request, Lead $lead)
    {
        $this->authorizeOwnerLead($lead);

        $data = $request->validate([
            'status' => 'required|in:' . implode(',', self::VALID_STATUSES),
            'remark' => 'nullable|string|max:2000',
            'follow_up_date' => 'nullable|date',
        ]);

        $oldStatus = $lead->status;

        \DB::table('leads')->where('id', $lead->id)->update([
            'status' => $data['status'],
            'next_follow_up_at' => $data['follow_up_date'] ?? $lead->next_follow_up_at,
            'updated_at' => now(),
        ]);

        \DB::table('lead_status_history')->insert([
            'lead_id' => $lead->id,
            'updated_by' => auth()->id(),
            'old_status' => $oldStatus,
            'new_status' => $data['status'],
            'remark' => $data['remark'] ?? null,
            'created_at' => now(),
        ]);

        $redirectToTenant = null;
        if ($data['status'] === 'deal_closed' && !$lead->converted_tenant_id) {
            $redirectToTenant = route('owner.tenants.create', ['lead_id' => $lead->id]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Status updated',
                'redirect_to_tenant' => $redirectToTenant,
            ]);
        }

        if ($redirectToTenant) {
            return redirect($redirectToTenant);
        }

        return back()->with('success', '✓ Lead status updated to ' . str_replace('_', ' ', $data['status']));
    }

    /**
     * Correct a lead's tenant/owner classification manually — the
     * automatic rule (property-tied enquiry = tenant) occasionally gets
     * it wrong (e.g. someone submits their own listing on the wrong form).
     */
    public function updateInquiryType(Request $request, Lead $lead)
    {
        $this->authorizeOwnerLead($lead);

        $data = $request->validate([
            'inquiry_type' => 'required|in:tenant,owner,unknown',
        ]);

        $lead->update(['inquiry_type' => $data['inquiry_type']]);

        return back()->with('success', '✓ Lead type updated.');
    }

    public function addRemark(Request $request, Lead $lead)
    {
        $this->authorizeOwnerLead($lead);

        $data = $request->validate([
            'remark' => 'required|string|max:2000',
        ]);

        \DB::table('lead_status_history')->insert([
            'lead_id' => $lead->id,
            'updated_by' => auth()->id(),
            'old_status' => $lead->status,
            'new_status' => $lead->status,
            'remark' => $data['remark'],
            'created_at' => now(),
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Remark added']);
        }
        return back()->with('success', '✓ Remark added');
    }

    public function timeline(Lead $lead)
    {
        $this->authorizeOwnerLead($lead);

        $history = \DB::table('lead_status_history')
            ->leftJoin('users', 'users.id', '=', 'lead_status_history.updated_by')
            ->where('lead_id', $lead->id)
            ->orderByDesc('lead_status_history.created_at')
            ->select('lead_status_history.*', 'users.name as updated_by_name')
            ->get();

        return response()->json(['success' => true, 'data' => $history]);
    }
    
    
    public function show(Lead $lead)
    {
        $this->authorizeOwnerLead($lead);

        $history = \DB::table('lead_status_history')
            ->leftJoin('users', 'users.id', '=', 'lead_status_history.updated_by')
            ->where('lead_id', $lead->id)
            ->orderByDesc('lead_status_history.created_at')
            ->select('lead_status_history.*', 'users.name as updated_by_name')
            ->get();

        return view('owner.lead-detail', compact('lead', 'history'));
    }
    
    

    private function authorizeOwnerLead(Lead $lead): void
    {
        $owner = auth()->user();
        $isUnlocked = \DB::table('lead_unlocks')
            ->where('lead_id', $lead->id)
            ->where('owner_id', $owner->id)
            ->exists();

        if (!$isUnlocked) {
            abort(403, 'You do not have access to manage this lead.');
        }
    }
}