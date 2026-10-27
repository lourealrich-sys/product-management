<?php

use App\Actions\CreateOrder;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\QueryException;
use RuntimeException;

test('order management pages require authentication', function () {
    $this->get(route('orders.index'))->assertRedirect(route('login'));
    $this->get(route('orders.create'))->assertRedirect(route('login'));
    $this->post(route('orders.store'))->assertRedirect(route('login'));
});

test('staff can view the order number customer staff total and status', function () {
    $staff = User::factory()->create(['name' => 'Casey Staff']);
    $customer = Customer::create([
        'name' => 'Morgan Customer',
        'email' => 'morgan@example.com',
        'phone' => '555-0123',
        'address' => '10 Main Street',
    ]);
    $category = Category::create(['name' => 'Test Category']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Test Product',
        'price' => '10.00',
        'stock_quantity' => 10,
        'is_available' => true,
    ]);
    $order = app(CreateOrder::class)->handle([
        'customer_id' => $customer->id,
        'items' => [['product_id' => $product->id, 'quantity' => 2]],
    ], $staff);

    $this->actingAs($staff)
        ->get(route('orders.index'))
        ->assertOk()
        ->assertSee($order->order_number)
        ->assertSee('Morgan Customer')
        ->assertSee('Casey Staff')
        ->assertSee('20.00')
        ->assertSee('pending');
});

test('order creation uses database prices and preserves the original unit price', function () {
    $staff = User::factory()->create();
    $customer = Customer::create([
        'name' => 'Order Customer',
        'email' => 'order@example.com',
        'phone' => '555-0100',
        'address' => '20 Market Street',
    ]);
    $category = Category::create(['name' => 'Office']);
    $firstProduct = Product::create([
        'category_id' => $category->id,
        'name' => 'Pen Set',
        'price' => '12.50',
        'stock_quantity' => 20,
        'is_available' => true,
    ]);
    $secondProduct = Product::create([
        'category_id' => $category->id,
        'name' => 'Paper Pack',
        'price' => '5.25',
        'stock_quantity' => 20,
        'is_available' => true,
    ]);

    $response = $this->actingAs($staff)->post(route('orders.store'), [
        'customer_id' => $customer->id,
        'items' => [
            ['product_id' => $firstProduct->id, 'quantity' => 2, 'unit_price' => '0.01'],
            ['product_id' => $secondProduct->id, 'quantity' => 1],
        ],
    ]);

    $response->assertRedirect(route('orders.index'));
    $order = Order::query()->with('items')->firstOrFail();
    expect($order->total)->toBe('30.25')
        ->and($order->staff_id)->toBe($staff->id)
        ->and($order->customer_id)->toBe($customer->id)
        ->and($order->items)->toHaveCount(2);
    $this->assertDatabaseHas('order_items', [
        'order_id' => $order->id,
        'product_id' => $firstProduct->id,
        'quantity' => 2,
        'unit_price' => '12.50',
        'subtotal' => '25.00',
    ]);

    $firstProduct->update(['price' => '99.00']);
    expect($order->items->firstWhere('product_id', $firstProduct->id)->unit_price)->toBe('12.50');
});

test('an order requires at least one product', function () {
    $staff = User::factory()->create();
    $customer = Customer::create([
        'name' => 'Empty Order Customer',
        'email' => 'empty@example.com',
        'phone' => '555-0101',
        'address' => '30 Oak Avenue',
    ]);

    $this->actingAs($staff)
        ->from(route('orders.create'))
        ->post(route('orders.store'), ['customer_id' => $customer->id, 'items' => []])
        ->assertRedirect(route('orders.create'))
        ->assertSessionHasErrors('items');

    expect(Order::count())->toBe(0);
});

test('order and line items roll back together when saving an item fails', function () {
    $staff = User::factory()->create();
    $customer = Customer::create([
        'name' => 'Rollback Customer',
        'email' => 'rollback@example.com',
        'phone' => '555-0106',
        'address' => '80 Spruce Court',
    ]);
    $category = Category::create(['name' => 'Rollback Category']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Rollback Product',
        'price' => '8.00',
        'stock_quantity' => 10,
        'is_available' => true,
    ]);
    OrderItem::creating(fn () => throw new RuntimeException('Unable to save order item.'));

    expect(fn () => app(CreateOrder::class)->handle([
        'customer_id' => $customer->id,
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ], $staff))->toThrow(RuntimeException::class, 'Unable to save order item.');

    OrderItem::flushEventListeners();
    expect(Order::count())->toBe(0)
        ->and(OrderItem::count())->toBe(0);
});

test('payments can be split but cannot exceed the order total', function () {
    $staff = User::factory()->create();
    $customer = Customer::create([
        'name' => 'Payment Customer',
        'email' => 'payment@example.com',
        'phone' => '555-0102',
        'address' => '40 Pine Road',
    ]);
    $category = Category::create(['name' => 'Accessories']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Cable',
        'price' => '40.00',
        'stock_quantity' => 5,
        'is_available' => true,
    ]);
    $order = app(CreateOrder::class)->handle([
        'customer_id' => $customer->id,
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ], $staff);

    $this->actingAs($staff)->post(route('orders.payments.store', $order), [
        'payment_reference' => 'PAY-001',
        'payment_method' => 'card',
        'amount' => '15.00',
        'paid_at' => '2026-10-27 10:00:00',
    ])->assertRedirect(route('orders.index'));

    $this->post(route('orders.payments.store', $order), [
        'payment_reference' => 'PAY-002',
        'payment_method' => 'cash',
        'amount' => '25.00',
        'paid_at' => '2026-10-27 11:00:00',
    ])->assertRedirect(route('orders.index'));

    $this->from(route('orders.index'))->post(route('orders.payments.store', $order), [
        'payment_reference' => 'PAY-003',
        'payment_method' => 'card',
        'amount' => '0.01',
        'paid_at' => '2026-10-27 12:00:00',
    ])->assertRedirect(route('orders.index'))
        ->assertSessionHasErrors('amount');

    expect(Payment::where('order_id', $order->id)->count())->toBe(2);
});

test('seeders create the requested management data and can be rerun safely', function () {
    $this->seed();
    $this->seed();

    expect(User::where('email', 'staff@example.com')->count())->toBe(1)
        ->and(Category::count())->toBe(3)
        ->and(Product::count())->toBe(6)
        ->and(Customer::count())->toBe(3)
        ->and(Order::count())->toBe(3)
        ->and(OrderItem::count())->toBe(6)
        ->and(Payment::count())->toBe(3);

    expect(Order::query()->withCount('items')->get()->every(fn (Order $order): bool => $order->items_count >= 2))->toBeTrue();
});

test('products cannot be deleted while referenced by an order', function () {
    $staff = User::factory()->create();
    $customer = Customer::create([
        'name' => 'Referenced Customer',
        'email' => 'referenced-product@example.com',
        'phone' => '555-0103',
        'address' => '50 Birch Lane',
    ]);
    $category = Category::create(['name' => 'Referenced Product Category']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Referenced Product',
        'price' => '8.00',
        'stock_quantity' => 10,
        'is_available' => true,
    ]);
    app(CreateOrder::class)->handle([
        'customer_id' => $customer->id,
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ], $staff);

    $this->expectException(QueryException::class);
    $product->delete();
});

test('customers cannot be deleted while referenced by an order', function () {
    $staff = User::factory()->create();
    $customer = Customer::create([
        'name' => 'Referenced Customer',
        'email' => 'referenced-customer@example.com',
        'phone' => '555-0104',
        'address' => '60 Cedar Street',
    ]);
    $category = Category::create(['name' => 'Customer Reference Category']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Customer Reference Product',
        'price' => '8.00',
        'stock_quantity' => 10,
        'is_available' => true,
    ]);
    app(CreateOrder::class)->handle([
        'customer_id' => $customer->id,
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ], $staff);

    $this->expectException(QueryException::class);
    $customer->delete();
});

test('staff cannot be deleted while referenced by an order', function () {
    $staff = User::factory()->create();
    $customer = Customer::create([
        'name' => 'Staff Reference Customer',
        'email' => 'referenced-staff@example.com',
        'phone' => '555-0105',
        'address' => '70 Walnut Way',
    ]);
    $category = Category::create(['name' => 'Staff Reference Category']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Staff Reference Product',
        'price' => '8.00',
        'stock_quantity' => 10,
        'is_available' => true,
    ]);
    app(CreateOrder::class)->handle([
        'customer_id' => $customer->id,
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ], $staff);

    $this->expectException(QueryException::class);
    $staff->delete();
});
