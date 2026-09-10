<?php

namespace Database\Seeders;

use App\Models\Amenity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AmenitySeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            'WiFi' => '📶', 'AC' => '❄️', 'Geyser' => '🚿', 'Power Backup' => '🔋',
            'Food (3 meals)' => '🍱', 'Laundry' => '🧺', 'Housekeeping' => '🧹',
            'CCTV' => '📹', 'Security Guard' => '🛡️', 'Parking' => '🅿️',
            'Gym' => '🏋️', 'Lift' => '🛗', 'Refrigerator' => '🧊', 'Washing Machine' => '🧼',
            'Cupboard' => '🗄️', 'Single Bed' => '🛏️', 'Study Table' => '📚',
            'Hot Water' => '♨️', 'TV / Lounge' => '📺', 'Two-wheeler Parking' => '🛵',
        ];

        foreach ($items as $name => $icon) {
            Amenity::create([
                'name' => $name,
                'slug' => Str::slug($name),
                'icon' => $icon,
            ]);
        }
    }
}
