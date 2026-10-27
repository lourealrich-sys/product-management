<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Orders') }}</h2>
            <a href="{{ route('orders.create') }}" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                {{ __('Create order') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('Order number') }}</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('Customer') }}</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('Staff member') }}</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('Total') }}</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($orders as $order)
                                <tr>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">{{ $order->order_number }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">{{ $order->customer?->name ?? '—' }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">{{ $order->staff?->name ?? '—' }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-700">${{ number_format((float) $order->total, 2) }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm capitalize text-gray-700">{{ str_replace('_', ' ', $order->status) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">{{ __('No orders have been created yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($orders->hasPages())
                    <div class="border-t border-gray-200 px-6 py-4">
                        {{ $orders->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
