<?php

namespace App\Console\Commands;

use App\Models\Property;
use Illuminate\Console\Command;

class AuditPropertyCoordinates extends Command
{
    protected $signature = 'properties:audit-coordinates {--limit=25 : Max rows to list per problem}';

    protected $description = 'List live PGs whose map pin looks wrong (missing, shared, low precision, or far from their city)';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $props = Property::active()->where('is_verified', true)->with('city:id,name')->get(['id', 'name', 'city_id', 'latitude', 'longitude']);
        $this->info('Active verified PGs: ' . $props->count());

        $missing = $props->filter(fn ($p) => empty($p->latitude) || empty($p->longitude));
        $this->line('');
        $this->warn("No map pin: {$missing->count()}");
        $missing->take($limit)->each(fn ($p) => $this->line("  #{$p->id} {$p->name}"));

        $pinned = $props->reject(fn ($p) => empty($p->latitude) || empty($p->longitude));

        $shared = $pinned->groupBy(fn ($p) => round($p->latitude, 4) . ',' . round($p->longitude, 4))->filter(fn ($g) => $g->count() > 1);
        $this->line('');
        $this->warn('Several PGs on the exact same point: ' . $shared->count() . ' point(s)');
        $shared->take($limit)->each(fn ($g, $k) => $this->line("  {$k}: " . $g->map(fn ($p) => "#{$p->id} {$p->name}")->implode(' | ')));

        $coarse = $pinned->filter(fn ($p) => round($p->latitude, 2) == (float) $p->latitude && round($p->longitude, 2) == (float) $p->longitude);
        $this->line('');
        $this->warn("Rough pin (2 decimals or fewer, about 1 km precision): {$coarse->count()}");
        $coarse->take($limit)->each(fn ($p) => $this->line("  #{$p->id} {$p->name}  ({$p->latitude}, {$p->longitude})"));

        $outliers = collect();
        foreach ($pinned->groupBy('city_id') as $group) {
            if ($group->count() < 3) continue;
            $medLat = $group->pluck('latitude')->sort()->values()->get((int) floor($group->count() / 2));
            $medLng = $group->pluck('longitude')->sort()->values()->get((int) floor($group->count() / 2));
            foreach ($group as $p) {
                $km = $this->km($medLat, $medLng, $p->latitude, $p->longitude);
                if ($km > 40) $outliers->push([$p, $km, $p->city?->name]);
            }
        }
        $this->line('');
        $this->warn("Far (over 40 km) from the middle of their city's other PGs: {$outliers->count()}");
        $outliers->take($limit)->each(fn ($o) => $this->line("  #{$o[0]->id} {$o[0]->name} ({$o[2]}) is " . round($o[1]) . ' km away'));

        $this->line('');
        $this->info('Fix these from Admin > Properties > Edit: search the address or drag the map marker, then save.');

        return self::SUCCESS;
    }

    private function km(float $la1, float $lo1, float $la2, float $lo2): float
    {
        $r = 6371;
        $dLa = deg2rad($la2 - $la1);
        $dLo = deg2rad($lo2 - $lo1);
        $a = sin($dLa / 2) ** 2 + cos(deg2rad($la1)) * cos(deg2rad($la2)) * sin($dLo / 2) ** 2;

        return 2 * $r * asin(min(1, sqrt($a)));
    }
}
