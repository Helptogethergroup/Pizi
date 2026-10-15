<?php

namespace App\Notifications;

use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Daily summary for an owner: how many tenants are late with rent and how much.
 */
class RentOverdueDigest extends Notification
{
    use Queueable;

    public function __construct(public int $count, public float $totalDue, public array $names = []) {}

    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'rent_overdue',
            'title' => '⏰ Rent overdue',
            'message' => $this->message(),
            'url' => '/owner/rent',
            'icon' => '⏰',
        ];
    }

    private function message(): string
    {
        $amount = '₹' . number_format($this->totalDue, 0);
        $who = count($this->names) ? ' (' . implode(', ', array_slice($this->names, 0, 3)) . ($this->count > 3 ? ' and more' : '') . ')' : '';

        return $this->count === 1
            ? "1 tenant has ₹" . number_format($this->totalDue, 0) . " overdue{$who}"
            : "{$this->count} tenants have {$amount} overdue{$who}";
    }
}
