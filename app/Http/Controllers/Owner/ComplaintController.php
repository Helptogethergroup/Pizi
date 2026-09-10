<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\ComplaintComment;
use App\Models\ComplaintMedia;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ComplaintController extends Controller
{
    public function index(Request $request)
    {
        $this->checkAccess();
        $managedIds = auth()->user()->getManagedPropertyIds();

        $query = Complaint::whereIn('property_id', $managedIds)
            ->with('tenant', 'property');

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($category = $request->get('category')) {
            $query->where('category', $category);
        }

        if ($priority = $request->get('priority')) {
            $query->where('priority', $priority);
        }

        if ($search = $request->get('q')) {
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('ticket_number', 'like', "%{$search}%")
                  ->orWhereHas('tenant', fn($t) => $t->where('name', 'like', "%{$search}%"));
            });
        }

        $complaints = $query->latest()->paginate(20)->withQueryString();

        $stats = [
            'open' => Complaint::whereIn('property_id', $managedIds)->where('status', 'open')->count(),
            'in_progress' => Complaint::whereIn('property_id', $managedIds)->whereIn('status', ['assigned', 'in_progress'])->count(),
            'urgent' => Complaint::whereIn('property_id', $managedIds)->where('priority', 'urgent')->whereNotIn('status', ['resolved', 'closed', 'cancelled'])->count(),
            'resolved_month' => Complaint::whereIn('property_id', $managedIds)
                ->where('status', 'resolved')
                ->whereMonth('resolved_at', now()->month)
                ->count(),
        ];

        return view('owner.complaints.index', compact('complaints', 'stats'));
    }

    public function create(Request $request)
    {
        $this->checkAccess();

        $tenants = Tenant::whereIn('property_id', auth()->user()->getManagedPropertyIds())
            ->where('status', 'active')
            ->with('property')
            ->orderBy('name')
            ->get();

        $selectedTenant = $request->tenant_id ? $tenants->firstWhere('id', $request->tenant_id) : null;

        return view('owner.complaints.create', compact('tenants', 'selectedTenant'));
    }

    public function store(Request $request)
    {
        $this->checkAccess();

        $data = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'category' => 'required|in:plumbing,electrical,wifi,housekeeping,food,furniture,security,ac,water,other',
            'priority' => 'required|in:low,medium,high,urgent',
            'title' => 'required|string|max:200',
            'description' => 'nullable|string',
            'media.*' => 'nullable|file|mimes:jpg,jpeg,png,mp4|max:10240',
        ]);

        $tenant = Tenant::where('id', $data['tenant_id'])
            ->whereIn('property_id', auth()->user()->getManagedPropertyIds())
            ->firstOrFail();

        $complaint = Complaint::create([
            'ticket_number' => 'TKT-' . date('Ymd') . '-' . strtoupper(Str::random(4)),
            'tenant_id' => $tenant->id,
            'property_id' => $tenant->property_id,
            'owner_id' => $this->effectiveOwnerId(),
            'category' => $data['category'],
            'priority' => $data['priority'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => 'open',
        ]);

        // Upload media
        if ($request->hasFile('media')) {
            foreach ($request->file('media') as $file) {
                $isVideo = str_starts_with($file->getMimeType(), 'video/');
                $path = $file->store('complaints/' . $complaint->id, 'public');

                ComplaintMedia::create([
                    'complaint_id' => $complaint->id,
                    'media_type' => $isVideo ? 'video' : 'photo',
                    'file_path' => $path,
                    'uploaded_by_id' => auth()->id(),
                ]);
            }
        }

        return redirect()->route('owner.complaints.show', $complaint)
            ->with('success', '✓ Complaint registered. Ticket: ' . $complaint->ticket_number);
    }

    public function show(Complaint $complaint)
    {
        $this->authorize_owner($complaint);
        $complaint->load('tenant', 'property', 'media', 'comments', 'assignedTo');
        return view('owner.complaints.show', compact('complaint'));
    }

    public function assign(Request $request, Complaint $complaint)
    {
        $this->authorize_owner($complaint);

        $data = $request->validate([
            'assigned_to_name' => 'required|string|max:200',
            'assigned_to_phone' => 'nullable|string|max:20',
        ]);

        $complaint->update([
            'assigned_to_name' => $data['assigned_to_name'],
            'assigned_to_phone' => $data['assigned_to_phone'] ?? null,
            'assigned_at' => now(),
            'status' => 'assigned',
        ]);

        return back()->with('success', '✓ Assigned to ' . $data['assigned_to_name']);
    }

    public function changeStatus(Request $request, Complaint $complaint)
    {
        $this->authorize_owner($complaint);

        $data = $request->validate([
            'status' => 'required|in:open,assigned,in_progress,resolved,closed,cancelled',
            'resolution_notes' => 'nullable|string',
        ]);

        $update = ['status' => $data['status']];

        if (in_array($data['status'], ['resolved', 'closed']) && !$complaint->resolved_at) {
            $update['resolved_at'] = now();
        }

        if (!empty($data['resolution_notes'])) {
            $update['resolution_notes'] = $data['resolution_notes'];
        }

        $wasResolved = in_array($complaint->status, ['resolved', 'closed']);
        $complaint->update($update);

        if (!$wasResolved && in_array($data['status'], ['resolved', 'closed']) && $complaint->tenant && $complaint->tenant->phone) {
            try {
                app(\App\Services\WhatsAppService::class)->sendTemplate(
                    $complaint->tenant->phone,
                    'complaint_resolved',
                    [$complaint->tenant->name, $complaint->title]
                );
            } catch (\Exception $e) {
                \Log::warning('Complaint resolved WhatsApp alert failed: ' . $e->getMessage());
            }
        }

        return back()->with('success', '✓ Status updated to ' . $data['status']);
    }

    public function changePriority(Request $request, Complaint $complaint)
    {
        $this->authorize_owner($complaint);

        $data = $request->validate([
            'priority' => 'required|in:low,medium,high,urgent',
        ]);

        $complaint->update(['priority' => $data['priority']]);

        return back()->with('success', '✓ Priority updated.');
    }

    public function addComment(Request $request, Complaint $complaint)
    {
        $this->authorize_owner($complaint);

        $data = $request->validate([
            'comment' => 'required|string|max:2000',
            'is_internal' => 'nullable|boolean',
        ]);

        ComplaintComment::create([
            'complaint_id' => $complaint->id,
            'user_id' => auth()->id(),
            'author_name' => auth()->user()->name,
            'author_role' => auth()->user()->role,
            'comment' => $data['comment'],
            'is_internal' => $request->boolean('is_internal'),
        ]);

        return back()->with('success', '✓ Comment added.');
    }

    public function uploadMedia(Request $request, Complaint $complaint)
    {
        $this->authorize_owner($complaint);

        $request->validate([
            'media.*' => 'required|file|mimes:jpg,jpeg,png,mp4|max:10240',
        ]);

        foreach ($request->file('media', []) as $file) {
            $isVideo = str_starts_with($file->getMimeType(), 'video/');
            $path = $file->store('complaints/' . $complaint->id, 'public');

            ComplaintMedia::create([
                'complaint_id' => $complaint->id,
                'media_type' => $isVideo ? 'video' : 'photo',
                'file_path' => $path,
                'uploaded_by_id' => auth()->id(),
            ]);
        }

        return back()->with('success', '✓ Media uploaded.');
    }

    public function deleteMedia(ComplaintMedia $media)
    {
        $complaint = $media->complaint;
        $this->authorize_owner($complaint);
        $media->delete();
        return back()->with('success', '✓ Media removed.');
    }

    public function destroy(Complaint $complaint)
    {
        $this->authorize_owner($complaint);
        $complaint->media()->delete();
        $complaint->comments()->delete();
        $complaint->delete();
        return redirect()->route('owner.complaints.index')->with('success', '✓ Complaint deleted.');
    }

    /**
     * A complaint's owner_id must always be the real property owner —
     * never the PG Manager's own user id.
     */
    private function effectiveOwnerId()
    {
        $user = auth()->user();
        return $user->role === 'pg_manager' ? $user->owner_id : $user->id;
    }

    /**
     * Blocks access entirely if this PG Manager wasn't granted the
     * "Complaints" feature by their owner.
     */
    private function checkAccess(): void
    {
        if (!auth()->user()->hasFeature('complaints')) {
            abort(403, 'You do not have access to Complaints.');
        }
    }

    private function authorize_owner(Complaint $complaint): void
    {
        if (auth()->user()->isAdmin()) {
            return;
        }

        $this->checkAccess();

        if (!auth()->user()->getManagedPropertyIds()->contains($complaint->property_id)) {
            abort(403);
        }
    }
}