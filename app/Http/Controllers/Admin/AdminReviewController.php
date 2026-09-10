<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Traits\RecalcsRatings;
use Illuminate\Http\Request;

class AdminReviewController extends Controller
{
    use RecalcsRatings;

    /**
     * Moderation list with search + status filter.
     * ?status=pending|approved|rejected|spam|all   ?q=keyword
     */
    public function index(Request $request)
    {
        $status = $request->input('status', 'pending');
        $q      = trim((string) $request->input('q', ''));

        $reviews = Review::with('property:id,name,slug')
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('reviewer_name', 'like', "%{$q}%")
                      ->orWhere('comment', 'like', "%{$q}%")
                      ->orWhere('title', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $counts = [
            'pending'  => Review::where('status', 'pending')->count(),
            'approved' => Review::where('status', 'approved')->count(),
            'rejected' => Review::where('status', 'rejected')->count(),
            'spam'     => Review::where('status', 'spam')->count(),
        ];

        return view('admin.reviews.index', compact('reviews', 'status', 'q', 'counts'));
    }

    /**
     * Single review status change.
     */
    public function updateStatus(Request $request, Review $review)
    {
        $data = $request->validate([
            'status'      => 'required|in:pending,approved,rejected,spam',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $review->update($data);
        $this->recalcPropertyRating($review->property_id);

        return back()->with('admin_success', "Review #{$review->id} marked as {$data['status']}.");
    }

    /**
     * Bulk action on selected reviews.
     */
    public function bulk(Request $request)
    {
        $data = $request->validate([
            'ids'    => 'required|array',
            'ids.*'  => 'integer',
            'action' => 'required|in:approved,rejected,spam,delete',
        ]);

        $reviews = Review::whereIn('id', $data['ids'])->get();
        $propertyIds = $reviews->pluck('property_id')->unique();

        if ($data['action'] === 'delete') {
            Review::whereIn('id', $data['ids'])->delete();
        } else {
            Review::whereIn('id', $data['ids'])->update(['status' => $data['action']]);
        }

        foreach ($propertyIds as $pid) {
            $this->recalcPropertyRating($pid);
        }

        return back()->with('admin_success', count($data['ids']) . ' reviews updated.');
    }
}
