<x-layouts::frontend :title="__('Dashboard')">
    <x-frontend.layout.page :title="__('Welcome, :name', ['name' => $user->name])" :subtitle="__('Your books, orders and reading in one place.')">
        <x-slot:actions>
            <flux:button variant="primary" size="sm" icon="building-storefront" :href="route('shop')" wire:navigate>{{ __('Shop') }}</flux:button>
        </x-slot:actions>

        <div class="grid gap-5 md:grid-cols-3">
            <x-frontend.cards.dashboard-tile icon="local_library" tone="success" :label="__('Books you own')" :value="$ownedBooksCount" :hint="__('Free books are always readable too.')">
                <flux:button size="sm" :href="route('user.library')" wire:navigate>{{ __('Open my library') }}</flux:button>
            </x-frontend.cards.dashboard-tile>
            <x-frontend.cards.dashboard-tile icon="receipt_long" :label="__('Orders')" :value="$ordersCount" :hint="__('Member since :date', ['date' => $user->created_at->format('d M Y')])">
                <flux:button size="sm" :href="route('user.orders')" wire:navigate>{{ __('View orders') }}</flux:button>
            </x-frontend.cards.dashboard-tile>
            <x-frontend.cards.dashboard-tile icon="menu_book" :label="__('Books opened')" :value="$openedBooksCount"
                :hint="$openedBooksCount ? __('Keep up the good work!') : __('Open a book to get started.')" />
        </div>

        @if ($recentBooks->isNotEmpty())
            <x-frontend.cards.card :title="__('Continue reading')" :description="__('Books you opened recently.')">
                <div class="grid grid-cols-2 gap-3 md:grid-cols-3">
                    @foreach ($recentBooks as $book)
                        <x-frontend.cards.product-list-item wire:key="recent-book-{{ $book->id }}" :product="$book" as="button" type="button"
                            :meta="__('Opened :time', ['time' => $book->last_opened_at->diffForHumans()])"
                            x-data x-on:click="Livewire.dispatch('open-book', { bookId: {{ $book->id }} })" />
                    @endforeach
                </div>
            </x-frontend.cards.card>
        @endif

        @if ($recentOrders->isNotEmpty() || $newBooks->isNotEmpty())
            <x-frontend.cards.card :padded="false" @class(['grid overflow-hidden', 'divide-y divide-line lg:grid-cols-2 lg:divide-x lg:divide-y-0' => $recentOrders->isNotEmpty() && $newBooks->isNotEmpty()])>
                @if ($newBooks->isNotEmpty())
                    <x-frontend.cards.card :framed="false" :title="__('Recently added books')" :description="__('The newest books in the shop.')">
                        <x-slot:actions>
                            <flux:button size="sm" variant="ghost" icon-trailing="arrow-right" :href="route('shop')" wire:navigate>{{ __('Browse all') }}</flux:button>
                        </x-slot:actions>
                        <div class="grid gap-3">
                            @foreach ($newBooks as $book)
                                <x-frontend.cards.product-list-item wire:key="new-book-{{ $book->id }}" :product="$book" />
                            @endforeach
                        </div>
                    </x-frontend.cards.card>
                @endif

                @if ($recentOrders->isNotEmpty())
                    <x-frontend.cards.card :framed="false" :title="__('Recent orders')">
                        <x-slot:actions>
                            <flux:button size="sm" variant="ghost" icon-trailing="arrow-right" :href="route('user.orders')" wire:navigate>{{ __('All orders') }}</flux:button>
                        </x-slot:actions>
                        <div class="-mx-6 -mb-6 divide-y divide-zinc-200 dark:divide-zinc-800">
                            @foreach ($recentOrders as $order)
                                <x-frontend.orders.row wire:key="recent-order-{{ $order->id }}"
                                    :href="route('user.orders.detail', $order->public_order_id)"
                                    :number="$order->public_order_id"
                                    :date="$order->created_at->format('d M Y')"
                                    :total="$order->total"
                                    :items-count="$order->items_count"
                                    :status="$order->status->label()" :status-color="$order->status->color()"
                                    :payment="$order->payment_status->label()" :payment-color="$order->payment_status->color()" />
                            @endforeach
                        </div>
                    </x-frontend.cards.card>
                @endif
            </x-frontend.cards.card>
        @endif
    </x-frontend.layout.page>

    <livewire:frontend.reader.index />
</x-layouts::frontend>
