<?php

namespace App\Notifications;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeadGoingStale extends Notification
{
    use Queueable;

    public function __construct(public Lead $lead, public int $daysSinceContact) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'lead_stale',
            'title' => '🥶 Lead going cold',
            'message' => "{$this->lead->name} hasn't been contacted in {$this->daysSinceContact} days",
            'lead_id' => $this->lead->id,
            'url' => '/telecaller/leads/' . $this->lead->id,
            'icon' => '🥶',
        ];
    }
}
