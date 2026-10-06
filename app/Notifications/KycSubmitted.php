<?php

namespace App\Notifications;

use App\Notifications\Channels\FcmChannel;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class KycSubmitted extends Notification
{
    use Queueable;

    public function __construct(public Tenant $tenant) {}

    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'kyc_submitted',
            'title' => '🪪 KYC submitted',
            'message' => "{$this->tenant->name} submitted their KYC documents — needs your approval",
            'tenant_id' => $this->tenant->id,
            'url' => '/owner/tenants/' . $this->tenant->id,
            'icon' => '🪪',
        ];
    }
}
