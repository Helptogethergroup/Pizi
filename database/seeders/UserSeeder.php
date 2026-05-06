<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@pgfind.in',
            'phone' => '9999900001',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'Rajesh Sharma',
            'email' => 'owner@pgfind.in',
            'phone' => '9999900002',
            'password' => Hash::make('owner123'),
            'role' => 'owner',
        ]);

        User::create([
            'name' => 'Priya Singh',
            'email' => 'telecaller@pgfind.in',
            'phone' => '9999900003',
            'password' => Hash::make('caller123'),
            'role' => 'telecaller',
        ]);

        User::create([
            'name' => 'Amit Field',
            'email' => 'field@pgfind.in',
            'phone' => '9999900004',
            'password' => Hash::make('field123'),
            'role' => 'field_executive',
        ]);
    }
}
