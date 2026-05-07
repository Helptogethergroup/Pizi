<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CreditsAdded extends Notification
{
    use Queueable;

    public function __construct(
        public int $amount,
        public int $newBalance,
        public string $source = 'admin'
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $sourceLabel = match ($this->source) {
            'purchase' => 'your purchase',
            'admin_credit' => 'an admin top-up',
            'bonus' => 'a bonus credit',
            default => 'a credit transaction',
        };

        return (new MailMessage)
            ->subject('✅ Credits added to your wallet')
            ->greeting("Hi {$notifiable->name},")
            ->line("**{$this->amount} credits** have been added from {$sourceLabel}.")
            ->line("New balance: **{$this->newBalance} credits**")
            ->action('View Wallet', url('/owner/wallet'))
            ->line('Use these to unlock more leads!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'credits_added',
            'title' => '✅ Credits added',
            'message' => "+{$this->amount} credits · Balance: {$this->newBalance}",
            'url' => '/owner/wallet',
            'icon' => '💰',
        ];
    }
}