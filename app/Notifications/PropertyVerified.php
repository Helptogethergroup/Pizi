<?php

namespace App\Notifications;

use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Admin verified the owner's property: the Verified badge is now live.
 */
class PropertyVerified extends Notification
{
    use Queueable;

    public function __construct(public string $propertyName, public ?int $propertyId = null) {}

    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'property_verified',
            'title' => '✅ Property verified',
            'message' => "{$this->propertyName} now has the Verified badge. Tenants trust verified PGs more.",
            'property_id' => $this->propertyId,
            'url' => '/owner/properties',
            'icon' => '✅',
        ];
    }
}
