<?php

namespace App\Console\Commands;

use App\Models\RentBill;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;

class SendRentDueReminders extends Command
{
    protected $signature = 'rent:send-due-reminders {--days=3 : Days before due date to remind}';
    protected $description = 'Send WhatsApp reminders for rent bills due in N days (also catches overdue bills not yet reminded today)';

    public function handle(WhatsAppService $whatsapp): int
    {
        $daysBefore = (int) $this->option('days');
        $targetDate = now()->addDays($daysBefore)->toDateString();

        // Bills due exactly on the target date, OR already overdue —
        // but skip any bill already reminded today (avoid duplicate spam).
        $bills = RentBill::where('status', '!=', 'paid')
            ->where(function ($q) use ($targetDate) {
                $q->whereDate('due_date', $targetDate)
                  ->orWhere('status', 'overdue');
            })
            ->where(function ($q) {
                $q->whereNull('last_reminder_sent_at')
                  ->orWhereDate('last_reminder_sent_at', '!=', now()->toDateString());
            })
            ->with('tenant')
            ->get();

        $sent = 0;

        foreach ($bills as $bill) {
            if (!$bill->tenant || !$bill->tenant->phone) {
                continue;
            }

            $result = $whatsapp->sendTemplate(
                $bill->tenant->phone,
                'due_reminder',
                // ⚠️ Param order not yet verified against the approved "rent_due_reminder"
                // template body on Meta — check {{1}} {{2}} {{3}}... there before relying on this.
                [
                    $bill->tenant->name,
                    number_format($bill->due_amount, 0),
                    $bill->month_label,
                    $bill->due_date->format('d M Y'),
                    $bill->pay_url,
                ]
            );

            if ($result['ok']) {
                $bill->update(['last_reminder_sent_at' => now()]);
                $sent++;
            }
        }

        $this->info("Checked {$bills->count()} bills, sent {$sent} reminders.");
        return self::SUCCESS;
    }
}