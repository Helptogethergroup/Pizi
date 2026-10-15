<?php

namespace App\Notifications;

use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Leads the owner unlocked but has not followed up on.
 */
class LeadsWaiting extends Notification
{
    use Queueable;

    public function __construct(public int $count) {}

    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'leads_waiting',
            'title' => '📞 Leads waiting for your call',
            'message' => $this->count === 1
                ? 'You unlocked a lead 3+ days ago and have not called yet.'
                : "{$this->count} leads you unlocked are still waiting for your call.",
            'url' => '/owner/leads',
            'icon' => '📞',
        ];
    }
}
