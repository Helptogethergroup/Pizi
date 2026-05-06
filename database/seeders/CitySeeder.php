<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Locality;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        $cities = [
            'Delhi' => [
                'description' => 'Delhi has India\'s most diverse PG market — from budget hostels in Mukherjee Nagar for UPSC aspirants to upscale coliving in Saket. Whichever pocket you pick, expect strong metro connectivity and bustling local markets.',
                'localities' => [
                    'Mukherjee Nagar', 'Karol Bagh', 'Laxmi Nagar', 'Saket', 'Hauz Khas',
                    'Lajpat Nagar', 'Rohini', 'Pitampura', 'Dwarka', 'Janakpuri',
                    'Connaught Place', 'Patel Nagar', 'GTB Nagar', 'Vasant Kunj', 'Malviya Nagar',
                ],
            ],
            'Noida' => [
                'description' => 'Noida is the techie\'s favourite — modern coliving spaces, well-maintained PGs, and seamless metro access to all sectors. Sectors 18, 62 and Greater Noida are PG hotspots.',
                'localities' => [
                    'Sector 18', 'Sector 62', 'Sector 137', 'Sector 16', 'Sector 50',
                    'Sector 125', 'Sector 76', 'Greater Noida', 'Noida Extension', 'Sector 15',
                ],
            ],
            'Gurgaon' => [
                'description' => 'Gurgaon (Gurugram) is the premium end of the NCR PG market — corporate professionals working in Cyber City, Golf Course Road, and DLF phases find modern coliving and serviced PGs aplenty.',
                'localities' => [
                    'Cyber City', 'Sector 14', 'Sector 49', 'Sushant Lok', 'Sector 56',
                    'DLF Phase 1', 'DLF Phase 2', 'DLF Phase 3', 'Golf Course Road', 'Sohna Road',
                ],
            ],
            'Ghaziabad' => [
                'description' => 'Ghaziabad offers some of NCR\'s most affordable PGs, especially around Vaishali and Indirapuram, with great metro connectivity to Delhi.',
                'localities' => [
                    'Vaishali', 'Indirapuram', 'Vasundhara', 'Kaushambi', 'Raj Nagar Extension',
                ],
            ],
            'Faridabad' => [
                'description' => 'Faridabad PGs are budget-friendly with good road links to South Delhi and Gurgaon. Sector 15 and NIT areas are most active.',
                'localities' => ['Sector 15', 'NIT', 'Sector 21', 'Greenfield Colony'],
            ],
        ];

        $order = 0;
        foreach ($cities as $name => $data) {
            $city = City::create([
                'name' => $name,
                'slug' => Str::slug($name),
                'state' => 'Delhi NCR',
                'description' => $data['description'],
                'meta_title' => "PG in $name — Verified Hostels & Coliving | PGFind",
                'meta_description' => "Browse verified PGs in $name. Filter by budget, gender, locality. Free site visits, zero brokerage. Find your perfect home in 60 seconds.",
                'is_active' => true,
                'display_order' => $order++,
            ]);

            foreach ($data['localities'] as $locName) {
                Locality::create([
                    'city_id' => $city->id,
                    'name' => $locName,
                    'slug' => Str::slug($locName),
                    'description' => "Looking for a PG in $locName, $name? Browse verified listings with real photos and honest reviews.",
                    'meta_title' => "PG in $locName, $name | PGFind",
                    'meta_description' => "Verified PGs in $locName, $name. Boys, girls & coliving options. Filter by budget, book free site visit.",
                    'is_active' => true,
                ]);
            }
        }
    }
}
