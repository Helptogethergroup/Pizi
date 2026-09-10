<?php

namespace App\Notifications;

use App\Models\Property;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PropertySubmittedForVerification extends Notification
{
    use Queueable;

    public function __construct(public Property $property) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'property_submitted',
            'title' => '🏠 New property needs verification',
            'message' => "\"{$this->property->name}\" was submitted and is waiting for verification",
            'property_id' => $this->property->id,
            // '/admin/properties/{id}/assign' is a PATCH-only route (the
            // inline owner-assign dropdown on the list page) — visiting it
            // directly with a GET click gives "405 Method Not Allowed".
            // Send them to the list itself instead, where verify/assign
            // actions actually live.
            'url' => '/admin/properties',
            'icon' => '🏠',
        ];
    }
}
