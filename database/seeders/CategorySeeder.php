<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Electronics', 'description' => 'Everyday electronic accessories.'],
            ['name' => 'Home & Kitchen', 'description' => 'Useful items for the home.'],
            ['name' => 'Office Supplies', 'description' => 'Essentials for work and study.'],
        ] as $category) {
            Category::updateOrCreate(['name' => $category['name']], $category);
        }
    }
}
