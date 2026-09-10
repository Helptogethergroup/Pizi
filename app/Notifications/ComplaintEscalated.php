<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ComplaintEscalated extends Notification
{
    use Queueable;

    public function __construct(
        public int $complaintId,
        public string $ticketNumber,
        public string $title,
        public int $daysOpen
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'complaint_escalated',
            'title' => '⏰ Complaint unresolved',
            'message' => "\"{$this->title}\" ({$this->ticketNumber}) has been open for {$this->daysOpen} days",
            'complaint_id' => $this->complaintId,
            'url' => '/admin/complaints/' . $this->complaintId,
            'icon' => '⏰',
        ];
    }
}
