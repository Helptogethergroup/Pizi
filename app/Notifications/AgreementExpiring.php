<?php

namespace App\Notifications;

use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * An agreement is about to end: time to renew it or plan for the bed.
 */
class AgreementExpiring extends Notification
{
    use Queueable;

    public function __construct(public string $tenantName, public string $endsOn, public int $daysLeft, public ?int $agreementId = null) {}

    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'agreement_expiring',
            'title' => '📄 Agreement ending soon',
            'message' => "{$this->tenantName}'s agreement ends on {$this->endsOn} ({$this->daysLeft} days)",
            'agreement_id' => $this->agreementId,
            'url' => '/owner/agreements',
            'icon' => '📄',
        ];
    }
}
