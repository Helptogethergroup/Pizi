<?php

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NoticeSubmitted extends Notification
{
    use Queueable;

    public function __construct(public Tenant $tenant) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $date = $this->tenant->notice_date?->format('d M Y') ?? 'soon';

        return [
            'type' => 'notice_submitted',
            'title' => '🚪 Notice period submitted',
            'message' => "{$this->tenant->name} is moving out on {$date}",
            'tenant_id' => $this->tenant->id,
            'url' => '/owner/tenants/' . $this->tenant->id,
            'icon' => '🚪',
        ];
    }
}
