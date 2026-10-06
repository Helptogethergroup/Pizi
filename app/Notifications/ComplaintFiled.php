<?php

namespace App\Notifications;

use App\Notifications\Channels\FcmChannel;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ComplaintFiled extends Notification
{
    use Queueable;

    public function __construct(
        public string $ticketNumber,
        public string $title,
        public string $priority,
        public string $tenantName,
        public int $complaintId
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        $priorityIcon = match ($this->priority) {
            'urgent' => '🔴',
            'high' => '🟠',
            default => '🟡',
        };

        return [
            'type' => 'complaint_filed',
            'title' => "{$priorityIcon} New complaint filed",
            'message' => "{$this->tenantName}: \"{$this->title}\" ({$this->ticketNumber})",
            'complaint_id' => $this->complaintId,
            'url' => '/owner/complaints/' . $this->complaintId,
            'icon' => '📢',
        ];
    }
}
