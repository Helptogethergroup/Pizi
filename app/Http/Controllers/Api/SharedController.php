<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Services\FcmService;
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

    public function markRead(Request $request, $id)
    {
        if (Schema::hasTable('notifications')) {
            // Scoped to the caller — otherwise any logged-in user could mark
            // someone else's notification as read by guessing its id.
            DB::table('notifications')
                ->where('id', $id)
                ->where('notifiable_type', 'App\\Models\\User')
                ->where('notifiable_id', $request->user->id)
                ->update(['read_at' => now()]);
        }
        return $this->ok(['message' => 'Marked read']);
    }

    // ─── Push notifications: device token registration ──────────────────────
    // Call right after login (and whenever Firebase rotates the token).
    // A token belongs to ONE user at a time — if someone else logs in on the
    // same phone, the token moves to them.
    public function registerDeviceToken(Request $request)
    {
        $data = $request->validate([
            'token' => 'required|string|max:512',
            'platform' => 'nullable|in:android,ios,web',
        ]);

        $device = DeviceToken::updateOrCreate(
            ['token_hash' => DeviceToken::hashFor($data['token'])],
            [
                'user_id' => $request->user->id,
                'token' => $data['token'],
                'platform' => $data['platform'] ?? null,
                'session_expires_at' => $this->sessionExpiry($request),
                'last_used_at' => now(),
            ]
        );

        return $this->ok(['message' => 'Device registered', 'id' => $device->id]);
    }

    // Expiry of the bearer token this request was made with (already validated
    // by PiziAuthenticate), so a device stops getting pushes once that session ends.
    private function sessionExpiry(Request $request): ?\Illuminate\Support\Carbon
    {
        $decoded = base64_decode(substr((string) $request->header('Authorization'), 7), true);
        $expiry = $decoded ? (explode('|', $decoded)[2] ?? null) : null;

        return $expiry ? \Illuminate\Support\Carbon::createFromTimestamp((int) $expiry, config('app.timezone')) : null;
    }

    // Call on logout so a signed-out phone stops getting that user's pushes.
    public function removeDeviceToken(Request $request)
    {
        $request->validate(['token' => 'required|string|max:512']);

        DeviceToken::where('token_hash', DeviceToken::hashFor($request->input('token')))
            ->where('user_id', $request->user->id)
            ->delete();

        return $this->ok(['message' => 'Device removed']);
    }

    // Sends a test push to the caller's own registered devices, so the app
    // developer can verify the whole chain without waiting for a real lead.
    public function testPush(Request $request, FcmService $fcm)
    {
        $user = $request->user;
        $devices = $user->deviceTokens()->count();

        if (!$fcm->isConfigured()) {
            return $this->ok(['sent' => 0, 'devices' => $devices, 'message' => 'Push is not configured on the server yet (Firebase credentials missing).']);
        }

        $sent = $fcm->sendToUser($user, 'Pizi test notification', 'If you can read this, push notifications are working.', ['type' => 'test']);

        return $this->ok(['sent' => $sent, 'devices' => $devices]);
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
