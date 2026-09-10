<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Models\Payment;
use App\Models\RentPayment;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;

class SendAdminDailySummary extends Command
{
    protected $signature = 'admin:send-daily-summary';
    protected $description = 'Send admins a WhatsApp summary of the day — signups, leads, payments';

    public function handle(WhatsAppService $whatsapp): int
    {
        $today = now()->toDateString();

        $signups = User::whereDate('created_at', $today)->count();
        $leads = Lead::whereDate('created_at', $today)->count();

        $creditRevenue = Payment::where('status', 'paid')->whereDate('created_at', $today)->sum('amount_inr');
        $rentRevenue = RentPayment::whereDate('paid_at', $today)->sum('amount');
        $revenue = $creditRevenue + $rentRevenue;

        $admins = User::where('role', 'admin')->whereNotNull('phone')->get();
        $sent = 0;

        foreach ($admins as $admin) {
            $result = $whatsapp->sendTemplate(
                $admin->phone,
                'admin_daily_summary',
                [now()->format('d M Y'), $signups, $leads, number_format($revenue, 0)]
            );

            if ($result['ok']) {
                $sent++;
            }
        }

        $this->info("Summary sent to {$sent} admin(s): {$signups} signups, {$leads} leads, ₹{$revenue} revenue.");
        return self::SUCCESS;
    }
}
