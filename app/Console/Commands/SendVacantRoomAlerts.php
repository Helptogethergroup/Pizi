<?php

namespace App\Console\Commands;

use App\Models\Property;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;

class SendVacantRoomAlerts extends Command
{
    protected $signature = 'properties:send-vacant-alerts {--days=15 : Room must have been vacant at least this many days}';
    protected $description = 'Nudge owners whose properties have had vacant rooms for a while';

    public function handle(WhatsAppService $whatsapp): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        // A room is "stuck vacant" if it's had available_rooms > 0 continuously
        // since before the cutoff — approximated here via updated_at, since
        // there's no dedicated "went vacant on" column yet.
        $properties = Property::where('is_active', true)
            ->where('available_rooms', '>', 0)
            ->where('updated_at', '<=', $cutoff)
            ->with('owner')
            ->get();

        $sent = 0;

        foreach ($properties as $property) {
            if (!$property->owner || !$property->owner->phone) {
                continue;
            }

            // No "already alerted" column on properties yet — throttle via
            // cache instead so the same property doesn't get pinged daily.
            $cacheKey = "vacant_room_alert_sent_{$property->id}";
            if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                continue;
            }

            $result = $whatsapp->sendTemplate(
                $property->owner->phone,
                'vacant_room_alert',
                [
                    $property->owner->name,
                    $property->name,
                    $property->available_rooms,
                    now()->diffInDays($property->updated_at),
                ]
            );

            if ($result['ok']) {
                \Illuminate\Support\Facades\Cache::put($cacheKey, true, now()->addDays(7));
                $sent++;
            }
        }

        $this->info("Checked {$properties->count()} properties, sent {$sent} vacant-room alerts.");
        return self::SUCCESS;
    }
}
