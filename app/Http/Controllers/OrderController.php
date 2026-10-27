<?php

namespace App\Http\Controllers;

use App\Actions\CreateOrder;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(): View
    {
        $orders = Order::query()
            ->with(['customer:id,name', 'staff:id,name'])
            ->latest()
            ->paginate(15);

        return view('orders.index', compact('orders'));
    }

    public function create(): View
    {
        $customers = Customer::query()->orderBy('name')->get(['id', 'name']);
        $products = Product::query()
            ->where('is_available', true)
            ->with('category:id,name')
            ->orderBy('name')
            ->get(['id', 'category_id', 'name', 'price']);

        return view('orders.create', compact('customers', 'products'));
    }

    public function store(Request $request, CreateOrder $createOrder): RedirectResponse
    {
        $data = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $createOrder->handle($data, $request->user());

        return to_route('orders.index')->with('status', 'Order created successfully.');
    }
}
