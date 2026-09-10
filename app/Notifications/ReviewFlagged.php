<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReviewFlagged extends Notification
{
    use Queueable;

    public function __construct(public Review $review) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'review_flagged',
            'title' => '🚩 Review reported',
            'message' => "A review by {$this->review->reviewer_name} was reported — needs moderation",
            'review_id' => $this->review->id,
            'url' => '/admin/reviews',
            'icon' => '🚩',
        ];
    }
}
