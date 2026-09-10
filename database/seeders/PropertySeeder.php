<?php

namespace Database\Seeders;

use App\Models\Amenity;
use App\Models\City;
use App\Models\Locality;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PropertySeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::where('role', 'owner')->first();
        $localities = Locality::with('city')->get();
        $amenityIds = Amenity::pluck('id')->toArray();

        $covers = [
            'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=1200&q=80',
            'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=1200&q=80',
            'https://images.unsplash.com/photo-1598928506311-c55ded91a20c?w=1200&q=80',
            'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=1200&q=80',
            'https://images.unsplash.com/photo-1540518614846-7eded433c457?w=1200&q=80',
            'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=1200&q=80',
            'https://images.unsplash.com/photo-1560185007-cde436f6a4d0?w=1200&q=80',
            'https://images.unsplash.com/photo-1493809842364-78817add7ffb?w=1200&q=80',
        ];

        $namePrefixes = ['Sai', 'Krishna', 'Royal', 'Comfort', 'Urban', 'Greenwood', 'Sunshine', 'Elite', 'Premium', 'Cosy', 'Maple', 'Heritage', 'Pearl', 'Aurora', 'Shanti'];
        $nameSuffixes = ['PG', 'Stay', 'Residency', 'Coliving', 'House', 'Hostel', 'Nest', 'Homes'];

        $i = 0;
        foreach ($localities as $loc) {
            // 2-3 properties per locality
            $count = rand(2, 3);
            for ($n = 0; $n < $count; $n++) {
                $i++;
                $gender = ['male', 'female', 'unisex'][array_rand(['male', 'female', 'unisex'])];
                $rentMin = rand(5, 15) * 1000;
                $rentMax = $rentMin + rand(2, 8) * 1000;
                $name = $namePrefixes[array_rand($namePrefixes)] . ' ' . $nameSuffixes[array_rand($nameSuffixes)] . ' — ' . $loc->name;

                $sharing = [];
                if (rand(0, 1)) $sharing['single'] = $rentMax;
                $sharing['double'] = $rentMin + rand(1000, 3000);
                if (rand(0, 1)) $sharing['triple'] = $rentMin;

                $property = Property::create([
                    'owner_id' => $owner->id,
                    'city_id' => $loc->city_id,
                    'locality_id' => $loc->id,
                    'name' => $name,
                    'slug' => Property::generateSlug($name),
                    'description' => "A clean, well-maintained PG in the heart of {$loc->name}, {$loc->city->name}. Walking distance from metro and major markets. " . ($gender === 'female' ? 'Exclusively for working women and female students with strong security and 24/7 warden.' : ($gender === 'male' ? 'A friendly boys-only PG with a homely atmosphere.' : 'Open to all genders. Modern coliving setup with private rooms and shared spaces.')),
                    'rules' => "No smoking inside the premises.\nGate closes at 11 PM.\nFood served 3 times a day.\nVisitors allowed in the lounge area only.",
                    'gender' => $gender,
                    'property_type' => ['pg', 'pg', 'pg', 'coliving', 'hostel'][array_rand([0,1,2,3,4])],
                    'rent_min' => $rentMin,
                    'rent_max' => $rentMax,
                    'security_deposit' => $rentMin,
                    'food_included' => (bool) rand(0, 1),
                    'sharing_options' => $sharing,
                    'address_line' => 'Plot No. ' . rand(10, 200) . ', ' . $loc->name,
                    'landmark' => 'Near ' . ['Metro Station', 'Main Market', 'Park', 'College'][array_rand([0,1,2,3])],
                    'pincode' => '11' . rand(0, 9) . rand(100, 999),
                    'latitude' => 28.4 + (mt_rand(0, 8000) / 10000),
                    'longitude' => 77.0 + (mt_rand(0, 6000) / 10000),
                    'is_active' => true,
                    'is_verified' => rand(0, 9) > 1, // ~80% verified
                    'is_featured' => $i % 7 === 0,   // every 7th is featured
                    'total_rooms' => rand(15, 50),
                    'available_rooms' => rand(0, 10),
                    'cover_image' => $covers[$i % count($covers)],
                    'view_count' => rand(50, 2000),
                    'lead_count' => rand(2, 80),
                ]);

                // Random amenities
                $randomAmenities = collect($amenityIds)->shuffle()->take(rand(6, 12))->all();
                $property->amenities()->sync($randomAmenities);
            }
        }
    }
}
