<?php

namespace App\Notifications;

use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** One notice about the owner's Bed Plan: renewed, ended (recharge needed), or about to renew. */
class BedPlanNotice extends Notification
{
    use Queueable;

    public function __construct(public string $kind, public string $message, public ?string $url = '/owner/bed-wallet') {}

    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        $titles = [
            'expired' => 'Bed Plan ended: recharge now',
            'renewed' => 'Bed Plan renewed',
            'low' => 'Bed Plan renews soon',
        ];

        return [
            'type' => 'bed_plan_' . $this->kind,
            'title' => $titles[$this->kind] ?? 'Bed Plan',
            'message' => $this->message,
            'url' => $this->url,
            'icon' => $this->kind === 'renewed' ? '✅' : '⚠️',
        ];
    }
}
