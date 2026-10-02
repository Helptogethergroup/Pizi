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
        if (!method_exists($notification, 'toFcm')) {
            return;
        }

        try {
            $payload = $notification->toFcm($notifiable);
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
}
