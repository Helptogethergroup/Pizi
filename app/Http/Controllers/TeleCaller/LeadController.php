<?php

namespace App\Http\Controllers\TeleCaller;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Lead;
use App\Models\Property;
use App\Models\FieldVisit;
use App\Models\User;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        // Shared pool — every telecaller sees the same full leads list
        // (not just leads assigned to them). assigned_telecaller_id stays
        // for round-robin/reporting, it just isn't a visibility filter.
        $base = Lead::query();

        // Counts for the Tenant/Owner tabs — computed from the same base
        // scope, before the inquiry_type filter.
        $tenantCount = (clone $base)->where('inquiry_type', 'tenant')->count();
        $ownerCount = (clone $base)->where('inquiry_type', 'owner')->count();

        $q = clone $base;

        if ($request->filled('inquiry_type')) {
            $q->where('inquiry_type', $request->inquiry_type);
        }
        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }
        // Lead Type Filter
        if ($request->filled('lead_type')) {
            $q->where('lead_type', $request->lead_type);
        }
        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $q->where(fn($x) => $x->where('name', 'like', $term)->orWhere('phone', 'like', $term));
        }

        $leads = $q->latest()->paginate(20)->withQueryString();
        return view('telecaller.leads', compact('leads', 'tenantCount', 'ownerCount'));
    }

 public function show(Lead $lead)
    {
        $this->authorizeAccess($lead);
        $lead->load('property', 'telecaller');

        $isOwnerClaimed = false;
        if ($lead->is_locked && $lead->locked_by_user_id) {
            $owner = User::find($lead->locked_by_user_id);
            $isOwnerClaimed = $owner && $owner->role === 'owner' ? $owner->name : false;
        }

        // Find matching properties for this lead based on current preferences
        $matchingProperties = $this->findMatchingProperties($lead);

        $fieldExecs = User::where('role', 'field_executive')->where('is_active', true)->get();
        $cities = City::orderBy('name')->get();

        return view('telecaller.lead-detail', compact('lead', 'matchingProperties', 'fieldExecs', 'isOwnerClaimed', 'cities'));
    }

    public function update(Request $request, Lead $lead)
    {
        $this->authorizeAccess($lead);
        $this->blockIfOwnerClaimed($lead);

        $data = $request->validate([
            'name' => 'nullable|string|max:120',
            'phone' => 'nullable|string|max:15',
            'email' => 'nullable|email|max:160',
            'preferred_locality' => 'nullable|string|max:120',
            'preferred_city' => 'nullable|string|max:120',
            'preferred_gender' => 'nullable|in:male,female,unisex',
            'budget_min' => 'nullable|numeric|min:0',
            'budget_max' => 'nullable|numeric|min:0',
            'move_in_date' => 'nullable|date',
            'status' => 'nullable|in:new,contacted,interested,follow_up,visit_scheduled,visit_done,closed_won,closed_lost,not_interested',
            'notes' => 'nullable|string|max:2000',
        ]);

        $filtered = array_filter($data, fn($v) => $v !== null);

        // Status update hote hi call_status + called_at bhi bhar do (journey Step 3)
        $callStatuses = ['contacted', 'interested', 'follow_up', 'visit_scheduled', 'visit_done', 'closed_won', 'not_interested'];
        if (isset($filtered['status']) && in_array($filtered['status'], $callStatuses)) {
            if (empty($lead->called_at)) {
                $filtered['called_at'] = now();
            }
            if (empty($lead->call_status)) {
                $filtered['call_status'] = $filtered['status'] === 'interested' ? 'interested' : 'contacted';
            }
        }

        $lead->update($filtered);

        // AJAX response — for live update
        if ($request->wantsJson() || $request->ajax()) {
            $matches = $this->findMatchingProperties($lead->fresh());
            return response()->json([
                'ok' => true,
                'message' => '✓ Lead updated.',
                'lead' => $lead->fresh(),
                'matches_html' => view('telecaller._matching_properties', ['matchingProperties' => $matches])->render(),
            ]);
        }

        return back()->with('success', 'Lead updated.');
    }




    public function whatsappLink(Lead $lead, Property $property)
    {
        $this->authorizeAccess($lead);

        $msg = "Hi {$lead->name}, here are the PG details you asked about:\n\n"
            . "🏠 {$property->name}\n"
            . "📍 {$property->address_line}, " . ($property->locality?->name ?? '') . "\n"
            . "💰 Rent: ₹" . number_format($property->rent_min) . "–" . number_format($property->rent_max) . "/month\n"
            . "🛡️ Deposit: ₹" . number_format($property->security_deposit ?? 0) . "\n\n"
            . "View full details: " . route('property.show', $property->slug) . "\n\n"
            . "Reply YES to schedule a free site visit.\n— PGFind";

        $phone = preg_replace('/\D/', '', $lead->phone);
        $url = "https://wa.me/{$phone}?text=" . urlencode($msg);

        return redirect($url);
    }

    /**
     * Find matching properties based on lead's current budget + area.
     */
    private function findMatchingProperties(Lead $lead, int $limit = 8)
    {
        $q = Property::where('is_active', true)->where('is_verified', true)
            ->with(['city', 'locality', 'images']);

        // Filter by gender
        if ($lead->preferred_gender && $lead->preferred_gender !== 'unisex') {
            $q->whereIn('gender', [$lead->preferred_gender, 'unisex']);
        }

        // Filter by budget
        if ($lead->budget_max) {
            $q->where('rent_min', '<=', $lead->budget_max);
        }
        if ($lead->budget_min) {
            $q->where('rent_max', '>=', $lead->budget_min);
        }

        // Filter by locality (exact match) or city
        if ($lead->preferred_locality) {
            $q->whereHas('locality', fn($l) => $l->where('name', $lead->preferred_locality));
        } elseif ($lead->preferred_city) {
            $q->whereHas('city', fn($c) => $c->where('name', $lead->preferred_city));
        }

        return $q->orderBy('rent_min')->take($limit)->get();
    }
    
    
    


    /**
     * Helper: Refresh tenant journey stage
     */
    private function refreshTenantJourney($userId)
    {
        $user = User::find($userId);
        if (!$user || $user->role !== 'tenant') return;
        
        // Simply call onboarding which recalculates stages
        try {
            $controller = new \App\Http\Controllers\TenantPortalController();
            // Just refresh - actual logic is in TenantPortalController::onboarding
            \DB::table('users')->where('id', $userId)->update(['updated_at' => now()]);
        } catch (\Exception $e) {
            \Log::warning('Journey refresh failed: ' . $e->getMessage());
        }
    }

    private function authorizeAccess(Lead $lead): void
    {
        // Shared pool — any active telecaller (or admin) can view/work any
        // lead, not just the one it's assigned_telecaller_id points at.
        $user = auth()->user();
        if ($user->isAdmin() || $user->role === 'telecaller') return;
        abort(403, 'This lead is not assigned to you.');
    }
    
    
   public function markCalled(Request $request, Lead $lead)
    {
        $this->authorizeAccess($lead);
        $this->blockIfOwnerClaimed($lead);

        $data = $request->validate([
            'call_status' => 'required|in:contacted,interested,not_reachable,not_interested',
            'call_notes'  => 'nullable|string|max:2000',
            'rejection_reason' => 'required_if:call_status,not_interested|nullable|in:too_expensive,already_found_pg,wrong_location,not_ready_yet,no_response_after_interest,other',
        ]);

        $outcome = $data['call_status'];
        $update = [
            'call_status' => $outcome,
            'call_notes'  => $data['call_notes'] ?? $lead->call_notes,
            'called_at'   => now(),
            'rejection_reason' => $outcome === 'not_interested' ? $data['rejection_reason'] : $lead->rejection_reason,
        ];

        if (in_array($outcome, ['contacted', 'interested'])) {
            $update['status'] = $outcome === 'interested' ? 'interested' : 'contacted';
            $flash = '✅ Call marked successful. You can now assign a field executive.';
        } elseif ($outcome === 'not_reachable') {
            $update['call_attempts'] = (int) ($lead->call_attempts ?? 0) + 1;
            $update['status'] = 'follow_up';
            $flash = "📵 Attempt #{$update['call_attempts']} logged. You can retry.";
        } else {
            $update['status'] = 'not_interested';
            $flash = '✗ Lead marked Not Interested and closed.';
        }

        // Auto-link lead to tenant user (Step 3 ke liye)
        if (empty($lead->user_id) && !empty($lead->phone)) {
            $last10 = substr(preg_replace('/[^0-9]/', '', $lead->phone), -10);
            $matchUser = \App\Models\User::whereRaw('RIGHT(REPLACE(REPLACE(phone, "+91", ""), " ", ""), 10) = ?', [$last10])
                ->where('role', 'tenant')->first();
            if ($matchUser) {
                $update['user_id'] = $matchUser->id;
            }
        }

        $lead->update($update);
        return back()->with('success', $flash);
    }

    public function scheduleVisit(Request $request, Lead $lead)
    {
        $this->authorizeAccess($lead);
        $this->blockIfOwnerClaimed($lead);

        if (!in_array($lead->call_status, ['contacted', 'interested'])) {
            return back()->with('error', '⛔ Mark the call as Contacted or Interested first.');
        }

        $data = $request->validate([
            'property_id'         => 'required|exists:properties,id',
            'scheduled_at'        => 'required|date|after:now',
            'field_executive_id'  => 'required|exists:users,id',
        ]);

        $fe = \App\Models\User::where('id', $data['field_executive_id'])
            ->where('role', 'field_executive')
            ->where('is_active', true)
            ->first();

        if (!$fe) {
            return back()->with('error', '⛔ Selected field executive is invalid or inactive.');
        }

        $visit = \App\Models\FieldVisit::create([
            'lead_id'             => $lead->id,
            'property_id'         => $data['property_id'],
            'scheduled_at'        => $data['scheduled_at'],
            'field_executive_id'  => $fe->id,
            'assigned_by_id'      => auth()->id(),
            'visit_type'          => 'tenant_visit',
            'status'              => 'scheduled',
            'outcome'             => 'pending',
        ]);

        $lead->update([
            'status'                       => 'visit_scheduled',
            'assigned_field_executive_id'  => $fe->id,
            'property_id'                  => $data['property_id'],
        ]);

        try {
            $visit->load('property');
            $fe->notify(new \App\Notifications\VisitScheduled($visit));
        } catch (\Exception $e) {
            \Log::warning('Visit notification failed: ' . $e->getMessage());
        }

        return back()->with('success', '✓ Field executive assigned and visit scheduled.');
    }
    
    
    
    public function markVerified(Lead $lead)
    {
        $this->authorizeAccess($lead);

        // A verified lead needs a city so it actually reaches the right
        // owners — without it, it falls into the "city unknown" bucket
        // shown to every owner instead of the ones who can act on it.
        if (!$lead->property_id && !$lead->preferred_city) {
            return back()->withErrors(['verify' => 'Please fill in the "Preferred City" field before verifying this lead.']);
        }

        $lead->update(['lead_type' => 'verified']);
        return back()->with('success', '✓ Lead marked as Verified.');
    }
    
    private function blockIfOwnerClaimed(Lead $lead): void
    {
        if ($lead->is_locked && $lead->locked_by_user_id) {
            $owner = User::find($lead->locked_by_user_id);
            if ($owner && $owner->role === 'owner') {
                abort(423, '🔒 This lead has been claimed by PG owner: ' . $owner->name . '. No further action allowed.');
            }
        }
    }
}