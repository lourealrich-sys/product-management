<?php

namespace App\Http\Controllers;

use App\Actions\RecordPayment;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function store(Request $request, Order $order, RecordPayment $recordPayment): RedirectResponse
    {
        $data = $request->validate([
            'payment_reference' => ['required', 'string', 'max:255'],
            'payment_method' => ['required', 'string', 'max:80'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0'],
            'paid_at' => ['required', 'date'],
        ]);

        $recordPayment->handle($order, $data);

        return to_route('orders.index')->with('status', 'Payment recorded successfully.');
    }
}
