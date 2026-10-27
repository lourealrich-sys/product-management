<?php

namespace App\Actions;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateOrder
{
    /**
     * @param  array{customer_id: int, items: list<array{product_id: int, quantity: int}>}  $data
     */
    public function handle(array $data, User $staff, ?string $orderNumber = null): Order
    {
        return DB::transaction(function () use ($data, $staff, $orderNumber): Order {
            $customer = Customer::query()->lockForUpdate()->findOrFail($data['customer_id']);
            $staff = User::query()->lockForUpdate()->findOrFail($staff->getKey());
            $productIds = array_column($data['items'], 'product_id');

            $products = Product::query()
                ->whereKey($productIds)
                ->where('is_available', true)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== count(array_unique($productIds))) {
                throw ValidationException::withMessages([
                    'items' => 'One or more selected products are unavailable.',
                ]);
            }

            $order = Order::create([
                'order_number' => $orderNumber ?? 'ORD-'.Str::ulid(),
                'customer_id' => $customer->getKey(),
                'staff_id' => $staff->getKey(),
                'total' => '0.00',
                'status' => 'pending',
            ]);

            $totalCents = 0;

            foreach ($data['items'] as $item) {
                $product = $products->get($item['product_id']);
                $quantity = (int) $item['quantity'];
                $unitPriceCents = Money::toCents($product->price);
                $subtotalCents = $unitPriceCents * $quantity;

                $order->items()->save(new OrderItem([
                    'product_id' => $product->getKey(),
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                    'subtotal' => Money::fromCents($subtotalCents),
                ]));

                $totalCents += $subtotalCents;
            }

            $order->update(['total' => Money::fromCents($totalCents)]);

            return $order;
        });
    }
}
