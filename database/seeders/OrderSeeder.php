<?php

namespace Database\Seeders;

use App\Actions\CreateOrder;
use App\Actions\RecordPayment;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $staff = User::query()->where('email', 'staff@example.com')->firstOrFail();
        $orders = [
            [
                'number' => 'ORD-DEMO-001',
                'customer' => 'alex@example.com',
                'status' => 'processing',
                'items' => [['USB-C Hub', 1], ['Wireless Mouse', 1]],
                'payments' => [['SEED-PAY-001-A', '35.00'], ['SEED-PAY-001-B', '39.98']],
            ],
            [
                'number' => 'ORD-DEMO-002',
                'customer' => 'jamie@example.com',
                'status' => 'pending',
                'items' => [['Ceramic Mug Set', 1], ['Desk Lamp', 1]],
                'payments' => [['SEED-PAY-002-A', '25.00']],
            ],
            [
                'number' => 'ORD-DEMO-003',
                'customer' => 'taylor@example.com',
                'status' => 'completed',
                'items' => [['Notebook Pack', 1], ['Monitor Stand', 1]],
                'payments' => [],
            ],
        ];

        foreach ($orders as $definition) {
            if (Order::query()->where('order_number', $definition['number'])->exists()) {
                continue;
            }

            $items = array_map(
                fn (array $item): array => [
                    'product_id' => Product::query()->where('name', $item[0])->firstOrFail()->getKey(),
                    'quantity' => $item[1],
                ],
                $definition['items'],
            );

            $order = app(CreateOrder::class)->handle(
                [
                    'customer_id' => Customer::query()->where('email', $definition['customer'])->firstOrFail()->getKey(),
                    'items' => $items,
                ],
                $staff,
                $definition['number'],
            );

            $order->update(['status' => $definition['status']]);

            foreach ($definition['payments'] as [$reference, $amount]) {
                app(RecordPayment::class)->handle($order, [
                    'payment_reference' => $reference,
                    'payment_method' => 'card',
                    'amount' => $amount,
                    'paid_at' => now()->toDateTimeString(),
                ]);
            }
        }
    }
}
