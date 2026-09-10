<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\ComplaintComment;
use App\Models\User;
use Illuminate\Http\Request;

class ComplaintController extends Controller
{
    public function index(Request $request)
    {
        $query = Complaint::with('tenant', 'property', 'owner');

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($priority = $request->get('priority')) {
            $query->where('priority', $priority);
        }

        if ($category = $request->get('category')) {
            $query->where('category', $category);
        }

        if ($ownerId = $request->get('owner_id')) {
            $query->where('owner_id', $ownerId);
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
            'open' => Complaint::where('status', 'open')->count(),
            'in_progress' => Complaint::whereIn('status', ['assigned', 'in_progress'])->count(),
            'urgent' => Complaint::where('priority', 'urgent')->whereNotIn('status', ['resolved', 'closed', 'cancelled'])->count(),
            'resolved_month' => Complaint::where('status', 'resolved')->whereMonth('resolved_at', now()->month)->count(),
            'total' => Complaint::count(),
        ];

        $owners = User::whereIn('role', ['owner', 'admin'])
            ->whereHas('complaintsAsOwner')
            ->orderBy('name')
            ->get();

        return view('admin.complaints.index', compact('complaints', 'stats', 'owners'));
    }

    public function show(Complaint $complaint)
    {
        $complaint->load('tenant', 'property', 'owner', 'media', 'comments', 'assignedTo');
        return view('admin.complaints.show', compact('complaint'));
    }

    public function changeStatus(Request $request, Complaint $complaint)
    {
        $request->validate([
            'status' => 'required|in:open,assigned,in_progress,resolved,closed,cancelled',
        ]);

        $update = ['status' => $request->status];

        if (in_array($request->status, ['resolved', 'closed']) && !$complaint->resolved_at) {
            $update['resolved_at'] = now();
        }

        if ($request->resolution_notes) {
            $update['resolution_notes'] = $request->resolution_notes;
        }

        $wasResolved = in_array($complaint->status, ['resolved', 'closed']);
        $complaint->update($update);

        if (!$wasResolved && in_array($request->status, ['resolved', 'closed']) && $complaint->tenant && $complaint->tenant->phone) {
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

        return back()->with('success', '✓ Status updated by admin.');
    }

    public function changePriority(Request $request, Complaint $complaint)
    {
        $request->validate(['priority' => 'required|in:low,medium,high,urgent']);
        $complaint->update(['priority' => $request->priority]);
        return back()->with('success', '✓ Priority updated.');
    }

    public function addComment(Request $request, Complaint $complaint)
    {
        $request->validate([
            'comment' => 'required|string|max:2000',
            'is_internal' => 'nullable|boolean',
        ]);

        ComplaintComment::create([
            'complaint_id' => $complaint->id,
            'user_id' => auth()->id(),
            'author_name' => auth()->user()->name . ' (Admin)',
            'author_role' => 'admin',
            'comment' => $request->comment,
            'is_internal' => $request->boolean('is_internal'),
        ]);

        return back()->with('success', '✓ Comment added.');
    }

    public function destroy(Complaint $complaint)
    {
        $complaint->media()->delete();
        $complaint->comments()->delete();
        $complaint->delete();
        return redirect()->route('admin.complaints.index')->with('success', '✓ Complaint deleted.');
    }
}