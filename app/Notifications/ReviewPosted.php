<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReviewPosted extends Notification
{
    use Queueable;

    public function __construct(public Review $review) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $stars = str_repeat('⭐', max(1, (int) $this->review->rating));

        return [
            'type' => 'review_posted',
            'title' => '📝 New review',
            'message' => ($this->review->reviewer_name ?: 'A tenant') . " left a {$stars} review on your property",
            'review_id' => $this->review->id,
            'url' => '/owner/reviews',
            'icon' => '📝',
        ];
    }
}
