<?php

namespace App\Notifications;

use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * A tenant who gave notice is about to move out, so the bed will be free.
 */
class NoticePeriodEnding extends Notification
{
    use Queueable;

    public function __construct(public string $tenantName, public string $moveOutOn, public int $daysLeft, public ?int $tenantId = null) {}

    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'notice_ending',
            'title' => '🚪 Tenant moving out soon',
            'message' => "{$this->tenantName} moves out on {$this->moveOutOn} ({$this->daysLeft} days). Get the bed ready for the next tenant.",
            'tenant_id' => $this->tenantId,
            'url' => '/owner/tenants',
            'icon' => '🚪',
        ];
    }
}
