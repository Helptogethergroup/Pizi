<?php

namespace App\Notifications;

use App\Models\Visit;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VisitScheduled extends Notification
{
    use Queueable;

    public function __construct(public Visit $visit) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('📅 New site visit scheduled')
            ->greeting("Hi {$notifiable->name},")
            ->line("A new visit has been assigned to you:")
            ->line("**Tenant:** {$this->visit->lead?->name} ({$this->visit->lead?->phone})")
            ->line("**Property:** {$this->visit->property?->name}")
            ->line("**When:** " . $this->visit->scheduled_at->format('d M Y, h:i A'))
            ->action('View Visit Details', url('/field/visits/' . $this->visit->id));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'visit_scheduled',
            'title' => '📅 New site visit',
            'message' => "Visit at {$this->visit->property?->name} on " . $this->visit->scheduled_at->format('d M, h:i A'),
            'visit_id' => $this->visit->id,
            'url' => '/field/visits/' . $this->visit->id,
            'icon' => '📅',
        ];
    }
}