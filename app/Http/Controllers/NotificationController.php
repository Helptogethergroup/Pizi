<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()->notifications()->paginate(20);
        return view('notifications.index', compact('notifications'));
    }

    /**
     * Get JSON of recent notifications (for header dropdown).
     */
    public function recent()
    {
        $notifications = auth()->user()->notifications()->take(10)->get();
        $unreadCount = auth()->user()->unreadNotifications()->count();

        return response()->json([
            'unread_count' => $unreadCount,
            'notifications' => $notifications->map(fn ($n) => [
                'id' => $n->id,
                'type' => $n->data['type'] ?? 'general',
                'title' => $n->data['title'] ?? '',
                'message' => $n->data['message'] ?? '',
                'icon' => $n->data['icon'] ?? '🔔',
                'url' => $n->data['url'] ?? '#',
                'read_at' => $n->read_at,
                'created_at' => $n->created_at->diffForHumans(),
            ]),
        ]);
    }

    public function markRead(string $id)
    {
        $notification = auth()->user()->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();
        }
        return back();
    }

    public function markAllRead()
    {
        auth()->user()->unreadNotifications->markAsRead();
        return back()->with('success', '✓ All notifications marked as read.');
    }
}