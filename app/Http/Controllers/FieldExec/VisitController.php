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
            ->with(['property.city', 'property.locality', 'lead']);

        if ($filter = $request->get('status')) {
            $query->where('status', $filter);
        }

        $visits = $query->latest('scheduled_at')->paginate(20);

        return view('field.visits.index', compact('visits'));
    }

    public function show(FieldVisit $visit)
    {
        $this->authorize_owner($visit);

        $visit->load('property.city', 'property.locality', 'property.amenities', 'lead', 'media', 'assignedBy');

        return view('field.visits.show', compact('visit'));
    }

    public function start(Request $request, FieldVisit $visit)
    {
        $this->authorize_owner($visit);

        $data = $request->validate([
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
        ]);

        $visit->update([
            'status' => 'in_progress',
            'started_at' => now(),
            'check_in_lat' => $data['lat'] ?? null,
            'check_in_lng' => $data['lng'] ?? null,
        ]);

        return back()->with('success', '✓ Visit started. Verify property details now.');
    }

    public function verify(Request $request, FieldVisit $visit)
    {
        $this->authorize_owner($visit);

        $visit->update([
            'address_verified' => $request->boolean('address_verified'),
            'amenities_verified' => $request->boolean('amenities_verified'),
            'rooms_verified' => $request->boolean('rooms_verified'),
            'safety_verified' => $request->boolean('safety_verified'),
            'remarks' => $request->remarks,
        ]);

        return back()->with('success', '✓ Verification updated.');
    }

    public function uploadMedia(Request $request, FieldVisit $visit)
    {
        $this->authorize_owner($visit);

        $request->validate([
            'media.*' => 'required|file|max:10240',
            'caption' => 'nullable|string|max:200',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
        ]);

        foreach ($request->file('media', []) as $file) {
            $isVideo = str_starts_with($file->getMimeType(), 'video/');
            $path = $file->store('field-visits/' . $visit->id, 'public');

            FieldVisitMedia::create([
                'field_visit_id' => $visit->id,
                'media_type' => $isVideo ? 'video' : 'photo',
                'file_path' => $path,
                'caption' => $request->caption,
                'captured_lat' => $request->lat,
                'captured_lng' => $request->lng,
            ]);
        }

        return back()->with('success', '✓ Media uploaded.');
    }

    public function complete(Request $request, FieldVisit $visit)
    {
        $this->authorize_owner($visit);

        $data = $request->validate([
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'remarks' => 'nullable|string',
        ]);

        $visit->update([
            'status' => 'completed',
            'completed_at' => now(),
            'check_out_lat' => $data['lat'] ?? null,
            'check_out_lng' => $data['lng'] ?? null,
            'remarks' => $data['remarks'] ?? $visit->remarks,
        ]);

        if ($visit->address_verified && $visit->amenities_verified && $visit->rooms_verified && $visit->safety_verified) {
            $visit->property->update(['is_verified' => true]);
        }

        return redirect()->route('field.visits.index')->with('success', '✓ Visit completed successfully!');
    }

    private function authorize_owner(FieldVisit $visit): void
    {
        if ($visit->field_executive_id !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403, 'You are not assigned to this visit.');
        }
    }
}