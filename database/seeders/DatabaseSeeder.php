<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CitySeeder::class,
            AmenitySeeder::class,
            PropertySeeder::class,
            BlogSeeder::class,
            LandmarkSeeder::class,
        ]);
    }
}
