<?php

namespace App\Http\Controllers\Field;

use App\Http\Controllers\Controller;
use App\Models\FieldVisit;
use App\Models\FieldVisitMedia;
use Illuminate\Http\Request;

class VisitController extends Controller
{
    public function index(Request $request)
    {
        $query = FieldVisit::where('field_executive_id', auth()->id())
            ->with(['property.city', 'property.locality']);

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $visits = $query->latest('scheduled_at')->paginate(20);

        return view('field.visits.index', compact('visits'));
    }

    public function show(FieldVisit $visit)
    {
        $this->ensureOwnership($visit);
        $visit->load('property.city', 'property.locality', 'property.amenities', 'media', 'assignedBy');
        return view('field.visits.show', compact('visit'));
    }

    public function start(Request $request, FieldVisit $visit)
    {
        $this->ensureOwnership($visit);

        $visit->update([
            'status' => 'in_progress',
            'started_at' => now(),
            'check_in_lat' => $request->lat,
            'check_in_lng' => $request->lng,
        ]);

        return back()->with('success', '✓ Visit started. Verify property details now.');
    }

    public function verify(Request $request, FieldVisit $visit)
    {
        $this->ensureOwnership($visit);

        $visit->update([
            'address_verified' => $request->boolean('address_verified'),
            'amenities_verified' => $request->boolean('amenities_verified'),
            'rooms_verified' => $request->boolean('rooms_verified'),
            'safety_verified' => $request->boolean('safety_verified'),
            'remarks' => $request->remarks,
        ]);

        return back()->with('success', '✓ Checklist updated.');
    }

    public function uploadMedia(Request $request, FieldVisit $visit)
    {
        $this->ensureOwnership($visit);

        $request->validate([
            'media.*' => 'required|file|max:10240',
            'caption' => 'nullable|string|max:200',
        ]);

        foreach ($request->file('media', []) as $file) {
            $isVideo = str_starts_with($file->getMimeType(), 'video/');
            $path = $file->store('field-visits/' . $visit->id, 'public');

            FieldVisitMedia::create([
                'field_visit_id' => $visit->id,
                'media_type' => $isVideo ? 'video' : 'photo',
                'file_path' => $path,
                'caption' => $request->caption,
            ]);
        }

        return back()->with('success', '✓ Media uploaded.');
    }

    public function complete(Request $request, FieldVisit $visit)
    {
        
        
        $this->ensureOwnership($visit);
        
                // ===== CHAIN VALIDATION: visit tabhi complete ho jab telecaller call + assign ho =====
        $lead = \App\Models\Lead::where('property_id', $visit->property_id)
            ->whereNotNull('user_id')
            ->orderByDesc('id')->first();

        if ($lead && $lead->user_id) {
            // Step 3 (call) hona chahiye
            if (!\App\Http\Controllers\TenantPortalController::isStepComplete($lead->user_id, 3)) {
                return back()->with('error', '⛔ Cannot complete visit — Telecaller has not marked the call yet (Step 3 pending).');
            }
        }
        // ===== END CHAIN VALIDATION =====

        $visit->update([
            'status' => 'completed',
            'completed_at' => now(),
            'check_out_lat' => $request->lat,
            'check_out_lng' => $request->lng,
            'remarks' => $request->remarks ?? $visit->remarks,
        ]);

        if ($visit->address_verified && $visit->amenities_verified
            && $visit->rooms_verified && $visit->safety_verified) {
            $visit->property->update(['is_verified' => true]);
        }

        return redirect()->route('field.visits.index')->with('success', '🎉 Visit completed successfully!');
    }

    private function ensureOwnership(FieldVisit $visit): void
    {
        if ($visit->field_executive_id !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403);
        }
    }
}