<?php

namespace App\Notifications;

use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * First of the month: how last month went.
 */
class MonthlySummary extends Notification
{
    use Queueable;

    public function __construct(public string $month, public float $collected, public float $due, public int $occupancyPercent) {}

    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'monthly_summary',
            'title' => "📊 {$this->month} summary",
            'message' => 'Collected ₹' . number_format($this->collected, 0)
                . ', still due ₹' . number_format($this->due, 0)
                . ", occupancy {$this->occupancyPercent}%",
            'url' => '/owner/dashboard',
            'icon' => '📊',
        ];
    }
}
