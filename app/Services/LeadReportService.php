<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadReport;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * "This lead was not real" - from the owner's report to the refund.
 */
class LeadReportService
{
    /** Owners can report within this many hours of unlocking the lead. */
    public function windowHours(): int
    {
        return (int) config('leads.report_hours', 72);
    }

    /**
     * An owner (or their manager) reports a lead they unlocked.
     *
     * @throws RuntimeException with a message that is safe to show
     */
    public function submit(User $user, Lead $lead, string $reason, ?string $note = null): LeadReport
    {
        if (!array_key_exists($reason, LeadReport::REASONS)) {
            throw new RuntimeException('Please choose what was wrong with this lead.');
        }
        $note = $note !== null ? trim($note) : null;
        if ($reason === 'other' && ($note === null || mb_strlen($note) < 5)) {
            throw new RuntimeException('Please write a few words about what was wrong.');
        }

        $teamOwnerId = $user->role === 'pg_manager' ? (int) $user->owner_id : (int) $user->id;

        $unlock = DB::table('lead_unlocks')
            ->where('lead_id', $lead->id)
            ->where('owner_id', $teamOwnerId)
            ->orderBy('id')
            ->first();
        if (!$unlock) {
            throw new RuntimeException('You can only report a lead you have unlocked.');
        }

        if (!empty($lead->converted_tenant_id)) {
            throw new RuntimeException('This lead became your tenant, so it cannot be reported.');
        }

        $hoursSince = now()->diffInHours(\Carbon\Carbon::parse($unlock->created_at), true);
        if ($hoursSince > $this->windowHours()) {
            throw new RuntimeException('Leads can be reported within ' . $this->windowHours() . ' hours of unlocking. This one is past that.');
        }

        if (LeadReport::where('lead_id', $lead->id)->where('owner_id', $teamOwnerId)->exists()) {
            throw new RuntimeException('You have already reported this lead.');
        }

        $report = LeadReport::create([
            'lead_id' => $lead->id,
            'owner_id' => $teamOwnerId,
            'reported_by' => $user->id,
            'paid_by' => $unlock->user_id ?? $teamOwnerId,
            'credits_spent' => (int) ($unlock->credits_spent ?? 0),
            'reason' => $reason,
            'note' => $note,
            'status' => 'pending',
        ]);

        try {
            User::where('role', 'admin')->get()->each(
                fn ($admin) => $admin->notify(new \App\Notifications\LeadReportedByOwner($report->load('lead', 'owner')))
            );
        } catch (\Throwable $e) {
            \Log::warning('Lead report notification failed: ' . $e->getMessage());
        }

        return $report;
    }

    /**
     * The team agrees: the credits go back to whoever paid, and a wrong-number or fake
     * lead is taken off everyone's list.
     *
     * @throws RuntimeException
     */
    public function approve(LeadReport $report, User $admin, ?string $adminNote = null, ?WalletService $wallets = null): LeadReport
    {
        $wallets ??= app(WalletService::class);

        return DB::transaction(function () use ($report, $admin, $adminNote, $wallets) {
            $report = LeadReport::lockForUpdate()->findOrFail($report->id);
            if ($report->status !== 'pending') {
                throw new RuntimeException('This report has already been reviewed.');
            }

            $refund = (int) $report->credits_spent;
            if ($refund > 0) {
                $payer = User::find($report->paid_by) ?? User::find($report->owner_id);
                if ($payer) {
                    $wallets->credit($payer, $refund, 'refund', 'lead-report-' . $report->id,
                        'Refund: lead reported as not genuine', $admin);
                }
            }

            $report->update([
                'status' => 'approved',
                'admin_note' => $adminNote,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'credits_refunded' => $refund,
            ]);

            if (in_array($report->reason, LeadReport::JUNK_REASONS, true)) {
                DB::table('leads')->where('id', $report->lead_id)->update([
                    'status' => 'junk',
                    'notes' => DB::raw("CONCAT(COALESCE(notes, ''), ' | Reported by owner: " . $report->reason . "')"),
                    'updated_at' => now(),
                ]);
            }

            $this->tellOwner($report, true);

            return $report;
        });
    }

    /** The team does not agree: no refund, and the owner is told why. */
    public function reject(LeadReport $report, User $admin, ?string $adminNote = null): LeadReport
    {
        return DB::transaction(function () use ($report, $admin, $adminNote) {
            $report = LeadReport::lockForUpdate()->findOrFail($report->id);
            if ($report->status !== 'pending') {
                throw new RuntimeException('This report has already been reviewed.');
            }

            $report->update([
                'status' => 'rejected',
                'admin_note' => $adminNote,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            $this->tellOwner($report, false);

            return $report;
        });
    }

    private function tellOwner(LeadReport $report, bool $approved): void
    {
        try {
            $owner = User::find($report->owner_id);
            $owner?->notify(new \App\Notifications\LeadReportReviewed($report->load('lead'), $approved));
        } catch (\Throwable $e) {
            \Log::warning('Lead report result notification failed: ' . $e->getMessage());
        }
    }
}
