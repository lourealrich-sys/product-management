<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Create order') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if ($customers->isEmpty() || $products->isEmpty())
                <div class="rounded-md bg-yellow-50 p-4 text-sm text-yellow-800">
                    {{ __('Add at least one customer and one available product before creating an order.') }}
                </div>
            @endif

            <form method="POST" action="{{ route('orders.store') }}" class="space-y-6 rounded-lg bg-white p-6 shadow-sm">
                @csrf

                <div>
                    <x-input-label for="customer_id" :value="__('Customer')" />
                    <select id="customer_id" name="customer_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">{{ __('Choose a customer') }}</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>{{ $customer->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('customer_id')" class="mt-2" />
                </div>

                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-4">
                        <h3 class="font-medium text-gray-900">{{ __('Products') }}</h3>
                        <button id="add-order-item" type="button" class="text-sm font-medium text-indigo-600 hover:text-indigo-900">
                            {{ __('Add another product') }}
                        </button>
                    </div>
                    <x-input-error :messages="$errors->get('items')" />

                    <div id="order-items" class="space-y-4">
                        @foreach (old('items', [[]]) as $index => $item)
                            <div class="grid grid-cols-1 items-end gap-4 sm:grid-cols-[1fr_10rem_auto]">
                                <div>
                                    <x-input-label :for="'product-'.$index" :value="__('Product')" />
                                    <select id="product-{{ $index }}" name="items[{{ $index }}][product_id]" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">{{ __('Choose a product') }}</option>
                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}" @selected(($item['product_id'] ?? '') == $product->id)>
                                                {{ $product->name }} ({{ $product->category->name }}) — ${{ number_format((float) $product->price, 2) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('items.'.$index.'.product_id')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label :for="'quantity-'.$index" :value="__('Quantity')" />
                                    <x-text-input :id="'quantity-'.$index" class="mt-1 block w-full" type="number" min="1" name="items[{{ $index }}][quantity]" :value="$item['quantity'] ?? 1" required />
                                    <x-input-error :messages="$errors->get('items.'.$index.'.quantity')" class="mt-2" />
                                </div>
                                <button type="button" class="remove-order-item rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50" @if ($loop->first) hidden @endif>
                                    {{ __('Remove') }}
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex items-center justify-end gap-4">
                    <a href="{{ route('orders.index') }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Cancel') }}</a>
                    <x-primary-button :disabled="$customers->isEmpty() || $products->isEmpty()">{{ __('Save order') }}</x-primary-button>
                </div>
            </form>

            <template id="order-item-template">
                <div class="grid grid-cols-1 items-end gap-4 sm:grid-cols-[1fr_10rem_auto]">
                    <div>
                        <label for="product-__INDEX__" class="block font-medium text-sm text-gray-700">{{ __('Product') }}</label>
                        <select id="product-__INDEX__" name="items[__INDEX__][product_id]" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">{{ __('Choose a product') }}</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->category->name }}) — ${{ number_format((float) $product->price, 2) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="quantity-__INDEX__" class="block font-medium text-sm text-gray-700">{{ __('Quantity') }}</label>
                        <input id="quantity-__INDEX__" type="number" min="1" name="items[__INDEX__][quantity]" value="1" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <button type="button" class="remove-order-item rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">{{ __('Remove') }}</button>
                </div>
            </template>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const container = document.getElementById('order-items');
                const template = document.getElementById('order-item-template');
                let nextIndex = container.children.length;

                document.getElementById('add-order-item').addEventListener('click', () => {
                    const row = template.content.cloneNode(true);
                    row.querySelectorAll('[name], [id], [for]').forEach((element) => {
                        ['name', 'id', 'for'].forEach((attribute) => {
                            if (element.hasAttribute(attribute)) {
                                element.setAttribute(attribute, element.getAttribute(attribute).replaceAll('__INDEX__', nextIndex));
                            }
                        });
                    });
                    container.append(row);
                    nextIndex++;
                });

                container.addEventListener('click', (event) => {
                    if (event.target.closest('.remove-order-item')) {
                        event.target.closest('.grid').remove();
                    }
                });
            });
        </script>
    @endpush
</x-app-layout>
