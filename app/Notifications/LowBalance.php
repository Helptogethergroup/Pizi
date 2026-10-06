<?php

namespace App\Notifications;

use App\Notifications\Channels\FcmChannel;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LowBalance extends Notification
{
    use Queueable;

    public function __construct(public int $balance) {}

    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'low_balance',
            'title' => '⚠️ Low credit balance',
            'message' => "Only {$this->balance} credits left. Recharge to keep unlocking leads.",
            'url' => '/owner/packages',
            'icon' => '⚠️',
        ];
    }
}