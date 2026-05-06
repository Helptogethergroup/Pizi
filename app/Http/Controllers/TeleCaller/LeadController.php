<?php

namespace App\Http\Controllers\TeleCaller;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Property;
use App\Models\Visit;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $q = Lead::where('assigned_telecaller_id', auth()->id())
            ->with('property');

        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $q->where(function ($qb) use ($term) {
                $qb->where('name', 'like', $term)
                   ->orWhere('phone', 'like', $term);
            });
        }

        $leads = $q->latest()->paginate(20)->withQueryString();
        return view('telecaller.leads', compact('leads'));
    }

    public function show(Lead $lead)
    {
        $this->authorizeAccess($lead);
        $lead->load(['property.images', 'property.amenities', 'visits.fieldExecutive']);
        return view('telecaller.lead-detail', compact('lead'));
    }

    public function update(Request $request, Lead $lead)
    {
        $this->authorizeAccess($lead);

        $data = $request->validate([
            'status' => 'required|in:new,contacted,interested,follow_up,visit_scheduled,visit_done,closed_won,closed_lost,junk',
            'telecaller_notes' => 'nullable|string',
            'next_follow_up_at' => 'nullable|date',
        ]);

        $data['last_contacted_at'] = now();
        $lead->update($data);

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

        Visit::create([
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

        return back()->with('success', 'Site visit scheduled.');
    }

    /**
     * Build a wa.me deep link with pre-filled property details.
     * One-click send from tele-caller dashboard.
     */
    public function whatsappLink(Lead $lead, Property $property)
    {
        $this->authorizeAccess($lead);

        $url = url('/pg/' . $property->slug);
        $msg = "Hi {$lead->name}, here are the PG details you asked about:\n\n"
            . "🏠 *{$property->name}*\n"
            . "📍 {$property->address_line}, " . ($property->locality?->name) . "\n"
            . "💰 Rent: " . $property->rent_range . "/month\n"
            . "🛡️ Deposit: ₹" . number_format($property->security_deposit) . "\n\n"
            . "View full details & photos: $url\n\n"
            . "Reply YES to schedule a free site visit. — PGFind";

        $waUrl = 'https://wa.me/' . preg_replace('/\D/', '', $lead->phone)
            . '?text=' . urlencode($msg);

        return redirect()->away($waUrl);
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
