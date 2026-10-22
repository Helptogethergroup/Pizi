<?php

namespace App\Notifications;

use App\Models\LeadReport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** To the admins: an owner says a lead they paid for was not real. */
class LeadReportedByOwner extends Notification
{
    use Queueable;

    public function __construct(public LeadReport $report) {}

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        $owner = $this->report->owner?->name ?? 'An owner';
        $lead = $this->report->lead?->name ?? 'a lead';

        return [
            'type' => 'lead_reported',
            'title' => '🚩 Lead reported',
            'lead_id' => $this->report->lead_id,
            'owner_id' => $this->report->owner_id,
            'message' => "{$owner} reported {$lead}: " . $this->report->reasonLabel() . " ({$this->report->credits_spent} credits)",
            'url' => '/admin/lead-reports',
            'icon' => '🚩',
        ];
    }
}
