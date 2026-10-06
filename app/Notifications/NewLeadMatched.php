<?php

namespace App\Notifications;

use App\Models\Lead;
use App\Notifications\Channels\FcmChannel;
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
        // Push sits before mail so an SMTP hiccup can't stop it (and the
        // push channel itself never throws).
        return ['database', FcmChannel::class, 'mail'];
    }

    public function toFcm(object $notifiable): array
    {
        $budget = $this->lead->budget_max ? ' · budget up to ₹' . number_format($this->lead->budget_max) : '';

        return [
            'title' => 'New lead matched to your property',
            'body' => "{$this->lead->name} · " . ($this->lead->preferred_locality ?: 'location not specified') . $budget,
            'data' => [
                'type' => 'new_lead',
                // Where the app should open when the owner taps the push.
                'screen' => 'lead_detail',
                'lead_id' => $this->lead->id,
                'match_score' => $this->matchScore,
            ],
        ];
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