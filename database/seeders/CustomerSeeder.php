<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Alex Morgan', 'email' => 'alex@example.com', 'phone' => '555-0101', 'address' => '100 Market Street'],
            ['name' => 'Jamie Lee', 'email' => 'jamie@example.com', 'phone' => '555-0102', 'address' => '200 Oak Avenue'],
            ['name' => 'Taylor Reed', 'email' => 'taylor@example.com', 'phone' => '555-0103', 'address' => '300 Pine Road'],
        ] as $customer) {
            Customer::updateOrCreate(['email' => $customer['email']], $customer);
        }
    }
}
