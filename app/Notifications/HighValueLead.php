<?php

namespace App\Notifications;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class HighValueLead extends Notification
{
    use Queueable;

    public function __construct(public Lead $lead) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'high_value_lead',
            'title' => '💎 High-value lead',
            'message' => "{$this->lead->name} — budget up to ₹" . number_format($this->lead->budget_max, 0),
            'lead_id' => $this->lead->id,
            'url' => '/admin/leads',
            'icon' => '💎',
        ];
    }
}
