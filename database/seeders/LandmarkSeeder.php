<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Landmark;
use App\Models\Property;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LandmarkSeeder extends Seeder
{
    public function run(): void
    {
        $delhi = City::where('slug', 'delhi')->first();
        $noida = City::where('slug', 'noida')->first();
        $gurgaon = City::where('slug', 'gurgaon')->first();

        if (!$delhi) return;

        $landmarks = [
            // DELHI — Universities & Colleges
            ['city' => $delhi, 'name' => 'Delhi University (North Campus)', 'type' => 'university', 'lat' => 28.6863, 'lng' => 77.2096, 'desc' => 'India\'s premier university with 80,000+ students. PGs in Mukherjee Nagar, GTB Nagar, and Kamla Nagar are walking distance.'],
            ['city' => $delhi, 'name' => 'IIT Delhi', 'type' => 'university', 'lat' => 28.5450, 'lng' => 77.1926, 'desc' => 'Top engineering institute. Hauz Khas and Munirka offer the best PG options nearby.'],
            ['city' => $delhi, 'name' => 'JNU (Jawaharlal Nehru University)', 'type' => 'university', 'lat' => 28.5378, 'lng' => 77.1684, 'desc' => 'Research university in South Delhi. Munirka and Vasant Kunj have many PGs.'],
            ['city' => $delhi, 'name' => 'Jamia Millia Islamia', 'type' => 'university', 'lat' => 28.5615, 'lng' => 77.2807, 'desc' => 'Central university in South-East Delhi. Batla House and Okhla are popular PG areas.'],
            ['city' => $delhi, 'name' => 'AIIMS Delhi', 'type' => 'hospital', 'lat' => 28.5672, 'lng' => 77.2100, 'desc' => 'India\'s top medical institute. PGs in Hauz Khas and Green Park serve medical students and staff.'],
            
            // DELHI — Metro
            ['city' => $delhi, 'name' => 'Rajiv Chowk Metro Station', 'type' => 'metro', 'lat' => 28.6328, 'lng' => 77.2197, 'desc' => 'Major interchange station in Connaught Place.'],
            ['city' => $delhi, 'name' => 'Hauz Khas Metro Station', 'type' => 'metro', 'lat' => 28.5435, 'lng' => 77.2065, 'desc' => 'Yellow + Magenta line interchange.'],
            
            // NOIDA
            ['city' => $noida, 'name' => 'Amity University Noida', 'type' => 'university', 'lat' => 28.5645, 'lng' => 77.3258, 'desc' => 'Sector 125 — largest private university campus. PGs in Sectors 125, 137, and Greater Noida cater to students.'],
            ['city' => $noida, 'name' => 'Galgotias University', 'type' => 'university', 'lat' => 28.4563, 'lng' => 77.4942, 'desc' => 'Greater Noida campus. Affordable PGs available in surrounding sectors.'],
            ['city' => $noida, 'name' => 'Noida Sector 18 (Atta Market)', 'type' => 'mall', 'lat' => 28.5687, 'lng' => 77.3261, 'desc' => 'Shopping & entertainment hub with DLF Mall of India.'],
            ['city' => $noida, 'name' => 'Sector 62 IT Park', 'type' => 'office', 'lat' => 28.6193, 'lng' => 77.3753, 'desc' => 'Tech hub with HCL, TCS, Wipro, IBM offices. PGs in Sector 62 are 5-10 min walk.'],
            ['city' => $noida, 'name' => 'Sector 16 Metro Station', 'type' => 'metro', 'lat' => 28.5778, 'lng' => 77.3251, 'desc' => 'Aqua line — connects to Botanical Garden interchange.'],
            
            // GURGAON
            ['city' => $gurgaon, 'name' => 'DLF Cyber City', 'type' => 'office', 'lat' => 28.4949, 'lng' => 77.0883, 'desc' => 'Gurgaon\'s biggest IT/corporate hub. PGs in DLF Phase 2/3 are walking distance.'],
            ['city' => $gurgaon, 'name' => 'Cyber Hub', 'type' => 'office', 'lat' => 28.4950, 'lng' => 77.0883, 'desc' => 'Premium F&B and entertainment district inside Cyber City.'],
            ['city' => $gurgaon, 'name' => 'IIT Gurgaon (formerly NIIT)', 'type' => 'university', 'lat' => 28.4575, 'lng' => 77.0266, 'desc' => 'Engineering & management institute in Sector 50.'],
            ['city' => $gurgaon, 'name' => 'IGI Airport (Delhi Airport)', 'type' => 'airport', 'lat' => 28.5562, 'lng' => 77.1000, 'desc' => 'Indira Gandhi International Airport. PGs in Mahipalpur and Aerocity for airport staff.'],
        ];

        foreach ($landmarks as $data) {
            if (!$data['city']) continue;
            $name = $data['name'];
            
            $landmark = Landmark::create([
                'city_id' => $data['city']->id,
                'name' => $name,
                'slug' => Str::slug($name),
                'type' => $data['type'],
                'latitude' => $data['lat'],
                'longitude' => $data['lng'],
                'description' => $data['desc'],
                'meta_title' => "PG near {$name} | Verified Hostels — PGFind",
                'meta_description' => "Find verified PGs near {$name}, {$data['city']->name}. Filter by budget, gender. Free site visits, zero brokerage.",
                'is_active' => true,
            ]);

            // Auto-link nearby properties using distance calculation
            $this->linkNearbyProperties($landmark);
        }
    }

    /**
     * Calculate distances and link properties within 8 km of each landmark.
     */
    private function linkNearbyProperties(Landmark $landmark): void
    {
        if (!$landmark->latitude || !$landmark->longitude) return;

        $properties = Property::where('city_id', $landmark->city_id)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        foreach ($properties as $property) {
            $distance = $this->haversine(
                $landmark->latitude, $landmark->longitude,
                $property->latitude, $property->longitude
            );

            if ($distance <= 8.0) {
                $landmark->properties()->syncWithoutDetaching([
                    $property->id => ['distance_km' => round($distance, 2)],
                ]);
            }
        }
    }

    /**
     * Haversine formula — distance between two GPS points in km.
     */
    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}