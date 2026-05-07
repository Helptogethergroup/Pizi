<?php

namespace App\Notifications;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewLeadMatched extends Notification
{
    use Queueable;

    public function __construct(public Lead $lead, public int $matchScore = 0)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url('/owner/leads');
        return (new MailMessage)
            ->subject('🎯 New lead matched to your property!')
            ->greeting("Hi {$notifiable->name},")
            ->line("A new lead is matched to your property — match score: **{$this->matchScore}/100**")
            ->line("**{$this->lead->name}** is looking for: " . ($this->lead->preferred_locality ?? 'Unspecified location') . ", budget ₹" . number_format($this->lead->budget_max ?? 0))
            ->action('View & Unlock Lead', $url)
            ->line('Unlock now to access contact details before others do.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_lead',
            'title' => '🎯 New lead matched!',
            'message' => "{$this->lead->name} matched your property (score: {$this->matchScore}/100)",
            'lead_id' => $this->lead->id,
            'match_score' => $this->matchScore,
            'url' => '/owner/leads',
            'icon' => '🎯',
        ];
    }
}