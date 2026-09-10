<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewOwnerSignup extends Notification
{
    use Queueable;

    public function __construct(public User $owner) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_owner_signup',
            'title' => '👋 New owner signed up',
            'message' => "{$this->owner->name} ({$this->owner->phone}) just registered as an owner",
            'owner_id' => $this->owner->id,
            'url' => '/admin/users',
            'icon' => '👋',
        ];
    }
}
