<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['name' => 'USB-C Hub', 'category' => 'Electronics', 'price' => '49.99', 'stock_quantity' => 25],
            ['name' => 'Wireless Mouse', 'category' => 'Electronics', 'price' => '24.99', 'stock_quantity' => 40],
            ['name' => 'Ceramic Mug Set', 'category' => 'Home & Kitchen', 'price' => '18.50', 'stock_quantity' => 30],
            ['name' => 'Desk Lamp', 'category' => 'Home & Kitchen', 'price' => '35.00', 'stock_quantity' => 18],
            ['name' => 'Notebook Pack', 'category' => 'Office Supplies', 'price' => '12.75', 'stock_quantity' => 50],
            ['name' => 'Monitor Stand', 'category' => 'Office Supplies', 'price' => '42.00', 'stock_quantity' => 15],
        ];

        foreach ($products as $product) {
            $category = Category::query()->where('name', $product['category'])->firstOrFail();

            Product::updateOrCreate(
                ['name' => $product['name']],
                [
                    'category_id' => $category->getKey(),
                    'description' => $product['name'],
                    'price' => $product['price'],
                    'stock_quantity' => $product['stock_quantity'],
                    'is_available' => true,
                ],
            );
        }
    }
}
