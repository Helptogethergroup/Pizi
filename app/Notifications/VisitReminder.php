<?php

namespace App\Notifications;

use App\Models\FieldVisit;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * In-app reminder sent to a field executive shortly before a scheduled
 * visit's time, so it doesn't get missed among everything else on their
 * plate for the day.
 */
class VisitReminder extends Notification
{
    use Queueable;

    public function __construct(public FieldVisit $visit) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'visit_reminder',
            'title' => '⏰ Visit starting soon',
            'message' => "{$this->visit->property?->name} at " . $this->visit->scheduled_at->format('h:i A'),
            'visit_id' => $this->visit->id,
            'url' => '/field/visits/' . $this->visit->id,
            'icon' => '⏰',
        ];
    }
}
