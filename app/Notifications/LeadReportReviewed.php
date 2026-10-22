<?php

namespace App\Notifications;

use App\Models\LeadReport;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** To the owner: what the Pizi team decided about a lead they reported. */
class LeadReportReviewed extends Notification
{
    use Queueable;

    public function __construct(public LeadReport $report, public bool $approved) {}

    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        $lead = $this->report->lead?->name ?? 'the lead';

        if ($this->approved) {
            $message = "We checked {$lead}. {$this->report->credits_refunded} credits are back in your wallet.";
        } else {
            $why = trim((string) $this->report->admin_note);
            $message = "We checked {$lead} and could not confirm the problem, so no credits were returned."
                . ($why !== '' ? " Note: {$why}" : '');
        }

        return [
            'type' => 'lead_report_reviewed',
            'title' => $this->approved ? '✅ Credits returned' : 'Lead report reviewed',
            'lead_id' => $this->report->lead_id,
            'message' => $message,
            'url' => '/owner/leads',
            'icon' => $this->approved ? '✅' : 'ℹ️',
        ];
    }
}
