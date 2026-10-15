<?php

namespace App\Notifications;

use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * A property has had empty beds for a while.
 */
class VacantBedsAlert extends Notification
{
    use Queueable;

    public function __construct(public string $propertyName, public int $vacant, public int $days, public ?int $propertyId = null) {}

    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'vacant_beds',
            'title' => '🛏️ Empty beds',
            'message' => "{$this->propertyName} has had {$this->vacant} vacant for {$this->days}+ days. Share a vacancy poster to fill them.",
            'property_id' => $this->propertyId,
            'url' => '/owner/rooms',
            'icon' => '🛏️',
        ];
    }
}
