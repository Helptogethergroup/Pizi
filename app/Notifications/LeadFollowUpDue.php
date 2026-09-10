<?php

namespace App\Notifications;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeadFollowUpDue extends Notification
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
            'type' => 'lead_followup_due',
            'title' => '⏰ Follow-up due',
            'message' => "Time to follow up with {$this->lead->name} ({$this->lead->phone})",
            'lead_id' => $this->lead->id,
            'url' => '/telecaller/leads/' . $this->lead->id,
            'icon' => '⏰',
        ];
    }
}
