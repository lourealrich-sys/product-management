<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StaffSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'staff@example.com'],
            [
                'name' => 'Store Staff',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ],
        );
    }
}
