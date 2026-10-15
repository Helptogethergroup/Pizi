<?php

namespace App\Notifications;

use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * A tenant paid the token / advance amount to hold a bed.
 */
class TokenPaymentReceived extends Notification
{
    use Queueable;

    public function __construct(public float $amount, public string $tenantName, public ?string $propertyName = null, public ?int $paymentId = null) {}

    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'token_received',
            'title' => '💰 Token payment received',
            'message' => $this->tenantName . ' paid ₹' . number_format($this->amount, 0) . ' to hold a bed' . ($this->propertyName ? " at {$this->propertyName}" : ''),
            'payment_id' => $this->paymentId,
            'url' => '/owner/token-payments',
            'icon' => '💰',
        ];
    }
}
