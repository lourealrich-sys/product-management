<?php

namespace App\Actions;

use App\Models\Order;
use App\Models\Payment;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordPayment
{
    /**
     * @param  array{payment_reference: string, payment_method: string, amount: string, paid_at: string}  $data
     */
    public function handle(Order $order, array $data): Payment
    {
        return DB::transaction(function () use ($order, $data): Payment {
            $order = Order::query()->lockForUpdate()->findOrFail($order->getKey());
            $paymentCents = Money::toCents($data['amount']);

            if ($paymentCents <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'The payment amount must be greater than zero.',
                ]);
            }

            $paidCents = $order->payments()
                ->pluck('amount')
                ->sum(fn (string $amount): int => Money::toCents($amount));

            if ($paidCents + $paymentCents > Money::toCents($order->total)) {
                throw ValidationException::withMessages([
                    'amount' => 'The total payments cannot exceed the order total.',
                ]);
            }

            return $order->payments()->create($data);
        });
    }
}
