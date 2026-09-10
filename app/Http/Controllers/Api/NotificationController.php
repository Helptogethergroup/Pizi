<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $items = DB::table('notifications')
            ->where('notifiable_type', 'App\\Models\\User')
            ->where('notifiable_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        $items = $items->map(function ($n) {
            $n->data = json_decode($n->data);
            return $n;
        });

        return response()->json(['success' => true, 'data' => $items]);
    }

    public function markRead(Request $request, $id)
    {
        DB::table('notifications')
            ->where('id', $id)
            ->where('notifiable_id', $request->user()->id)
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }
}
