<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\MonthlySummary;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * 1st of the month: last month's collected / due and today's occupancy.
 */
class SendOwnerMonthlySummary extends Command
{
    protected $signature = 'owner:send-monthly-summary';
    protected $description = 'Send each owner (app notification) a summary of last month';

    public function handle(): int
    {
        $last = now()->subMonthNoOverflow();
        $month = $last->format('Y-m');
        $label = $last->format('F Y');

        $owners = User::where('role', 'owner')->where('is_active', true)->get(['id']);
        $sent = 0;

        foreach ($owners as $owner) {
            $bills = DB::table('rent_bills')->where('owner_id', $owner->id)->where('month', $month);
            if (!(clone $bills)->exists()) {
                continue; // nothing was billed: no summary to send
            }

            $collected = (float) (clone $bills)->sum('paid_amount');
            $due = (float) (clone $bills)->sum('due_amount');

            $totalBeds = DB::table('beds')->where('owner_id', $owner->id)->whereNull('deleted_at')->count();
            $occupied = DB::table('beds')->where('owner_id', $owner->id)->whereNull('deleted_at')->where('status', 'occupied')->count();
            $percent = $totalBeds > 0 ? (int) round($occupied / $totalBeds * 100) : 0;

            User::find($owner->id)->notify(new MonthlySummary($label, $collected, $due, $percent));
            $sent++;
        }

        $this->info("Sent {$sent} monthly summaries for {$label}.");
        return self::SUCCESS;
    }
}
