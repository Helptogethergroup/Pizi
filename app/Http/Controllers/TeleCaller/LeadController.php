<?php

namespace App\Http\Controllers\TeleCaller;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Property;
use App\Models\Visit;
use App\Models\User;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $q = Lead::query();

        if (!auth()->user()->isAdmin()) {
            $q->where('assigned_telecaller_id', auth()->id());
        }

        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $q->where(fn($x) => $x->where('name', 'like', $term)->orWhere('phone', 'like', $term));
        }

        $leads = $q->latest()->paginate(20)->withQueryString();
        return view('telecaller.leads', compact('leads'));
    }

    public function show(Lead $lead)
    {
        $this->authorizeAccess($lead);
        $lead->load('property', 'telecaller');

        // Find matching properties for this lead based on current preferences
        $matchingProperties = $this->findMatchingProperties($lead);

        $fieldExecs = User::where('role', 'field_executive')->where('is_active', true)->get();

        return view('telecaller.lead-detail', compact('lead', 'matchingProperties', 'fieldExecs'));
    }

    public function update(Request $request, Lead $lead)
    {
        $this->authorizeAccess($lead);

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

        $lead->update(array_filter($data, fn($v) => $v !== null));

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

    public function scheduleVisit(Request $request, Lead $lead)
    {
        $this->authorizeAccess($lead);

        $data = $request->validate([
            'property_id' => 'required|exists:properties,id',
            'scheduled_at' => 'required|date|after:now',
            'field_executive_id' => 'nullable|exists:users,id',
        ]);

        $visit = Visit::create([
            'lead_id' => $lead->id,
            'property_id' => $data['property_id'],
            'scheduled_at' => $data['scheduled_at'],
            'field_executive_id' => $data['field_executive_id'] ?? null,
            'outcome' => 'pending',
        ]);

        $lead->update([
            'status' => 'visit_scheduled',
            'assigned_field_executive_id' => $data['field_executive_id'] ?? null,
        ]);

        // Notify field exec
        try {
            $visit->load('lead', 'property');
            if ($fieldExec = User::find($data['field_executive_id'] ?? null)) {
                $fieldExec->notify(new \App\Notifications\VisitScheduled($visit));
            }
        } catch (\Exception $e) {
            \Log::warning('Visit notification failed: ' . $e->getMessage());
        }

        return back()->with('success', 'Site visit scheduled.');
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

    private function authorizeAccess(Lead $lead): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) return;
        if ($lead->assigned_telecaller_id !== $user->id) {
            abort(403, 'This lead is not assigned to you.');
        }
    }
}