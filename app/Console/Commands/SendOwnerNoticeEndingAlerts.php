<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\NoticePeriodEnding;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Owners hear when a tenant who gave notice is about to move out.
 */
class SendOwnerNoticeEndingAlerts extends Command
{
    protected $signature = 'owner:notify-moveouts {--days=3 : Days before the move-out date}';
    protected $description = 'Tell owners (app notification) that a tenant on notice moves out soon';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $date = now()->addDays($days)->toDateString();

        $tenants = DB::table('tenants')
            ->whereNotNull('move_out_date')
            ->whereDate('move_out_date', $date)
            ->where('status', '!=', 'inactive')
            ->get(['id', 'owner_id', 'name', 'move_out_date']);

        $sent = 0;
        foreach ($tenants as $t) {
            $key = "moveout_alert_{$t->id}_{$date}";
            if (Cache::has($key)) {
                continue;
            }
            $owner = User::find($t->owner_id);
            if (!$owner) {
                continue;
            }
            $owner->notify(new NoticePeriodEnding(
                $t->name,
                \Carbon\Carbon::parse($t->move_out_date)->format('d M Y'),
                $days,
                (int) $t->id
            ));
            Cache::put($key, true, now()->addDays(10));
            $sent++;
        }

        $this->info("Sent {$sent} move-out alerts.");
        return self::SUCCESS;
    }
}
