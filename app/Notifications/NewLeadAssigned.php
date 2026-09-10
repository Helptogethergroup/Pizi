<?php

namespace App\Notifications;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewLeadAssigned extends Notification
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
            'type' => 'lead_assigned',
            'title' => '📋 New lead assigned',
            'message' => "{$this->lead->name} ({$this->lead->phone}) was assigned to you",
            'lead_id' => $this->lead->id,
            'url' => '/telecaller/leads/' . $this->lead->id,
            'icon' => '📋',
        ];
    }
}
