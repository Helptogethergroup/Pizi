<?php

namespace App\Notifications\Channels;

use App\Services\FcmService;
use Illuminate\Notifications\Notification;

/**
 * Add this class to a notification's via() and give the notification a
 * toFcm($notifiable) returning ['title' => ..., 'body' => ..., 'data' => [...]].
 * A push failure is swallowed here so it can never stop the notification's
 * other channels (database / mail).
 */
class FcmChannel
{
    public function __construct(private FcmService $fcm)
    {
    }

    public function send(object $notifiable, Notification $notification): void
    {
        try {
            $payload = method_exists($notification, 'toFcm')
                ? $notification->toFcm($notifiable)
                : $this->fromArray($notification->toArray($notifiable));
            if (!$payload) {
                return;
            }

            $this->fcm->sendToUser(
                $notifiable,
                $payload['title'],
                $payload['body'],
                $payload['data'] ?? []
            );
        } catch (\Throwable $e) {
            \Log::warning('FCM channel failed: ' . $e->getMessage());
        }
    }

    // Which app screen opens when the owner taps a push, keyed by the
    // notification's 'type'. The app falls back to home for unknown screens.
    private const SCREENS = [
        'payment_received' => 'rent_bill',
        'complaint_filed' => 'complaint_detail',
        'notice_submitted' => 'tenant_detail',
        'kyc_submitted' => 'tenant_detail',
        'agreement_signed' => 'tenant_detail',
        'review_posted' => 'reviews',
        'credits_added' => 'wallet',
        'low_balance' => 'recharge',
    ];

    /**
     * Notifications without their own toFcm() reuse their database payload:
     * title/message become the push text, and every *_id field plus the
     * type/screen travel as data for the app's deep link.
     */
    private function fromArray(array $d): ?array
    {
        if (empty($d['title']) || empty($d['message'])) {
            return null;
        }

        $type = $d['type'] ?? 'general';
        $data = ['type' => $type, 'screen' => self::SCREENS[$type] ?? 'home'];
        foreach ($d as $key => $value) {
            if (str_ends_with($key, '_id') && $value !== null) {
                $data[$key] = $value;
            }
        }

        return ['title' => $d['title'], 'body' => $d['message'], 'data' => $data];
    }
}
