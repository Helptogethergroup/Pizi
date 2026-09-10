<?php

namespace App\Notifications;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeadUnlockedByOwner extends Notification
{
    use Queueable;

    public function __construct(
        public Lead $lead,
        public User $owner
    ) {}

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'lead_unlocked',
            'title' => '🔓 Lead unlocked',
            'lead_id' => $this->lead->id,
            'lead_name' => $this->lead->name,
            'owner_id' => $this->owner->id,
            'owner_name' => $this->owner->name,
            'message' => '🔓 ' . $this->owner->name . ' (PG owner) unlocked lead: ' . $this->lead->name . '. Do not reassign.',
            'url' => '/admin/leads',
            'icon' => '🔓',
        ];
    }
}