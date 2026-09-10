<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SharedController extends Controller
{
    public function notifications(Request $request)
    {
        $uid = $request->user->id;
        // Try Laravel notifications table
        if (Schema::hasTable('notifications')) {
            $notifs = DB::table('notifications')
                ->where('notifiable_type', 'App\\Models\\User')
                ->where('notifiable_id', $uid)
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get()
                ->map(function ($n) {
                    $n->data = json_decode($n->data, true) ?? [];
                    return $n;
                });
            return $this->ok($notifs);
        }
        return $this->ok([]);
    }

    public function markRead($id)
    {
        if (Schema::hasTable('notifications')) {
            DB::table('notifications')->where('id', $id)->update(['read_at' => now()]);
        }
        return $this->ok(['message' => 'Marked read']);
    }

    public function leadManual(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'phone' => 'required|string',
        ]);

        // Duplicate check
        $existing = DB::table('leads')
            ->where('phone', $request->phone)
            ->where('created_at', '>=', now()->subDays(30))
            ->first();

        if ($existing && !$request->confirm_duplicate) {
            return response()->json([
                'success' => true,
                'data' => [
                    'duplicate' => [
                        'phone' => $existing->phone,
                        'status' => $existing->status,
                        'created_at' => $existing->created_at,
                    ],
                ],
            ]);
        }

        $leadId = DB::table('leads')->insertGetId([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'message' => $request->message,
            'property_id' => $request->property_id,
            'source' => $request->source ?? 'manual',
            'status' => 'new',
            'preferred_city' => $request->preferred_city,
            'preferred_locality' => $request->preferred_locality,
            'preferred_gender' => $request->preferred_gender,
            'move_in_date' => $request->move_in_date,
            'budget_min' => $request->budget_min,
            'budget_max' => $request->budget_max,
            'is_verified' => $request->boolean('mark_as_verified') ? 1 : 0,
            'credit_cost' => $request->boolean('mark_as_verified') ? 5 : 3,
            'created_by' => $request->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->ok(['lead_id' => $leadId, 'message' => 'Lead saved']);
    }

    private function ok($data) { return response()->json(['success' => true, 'data' => $data]); }
}
