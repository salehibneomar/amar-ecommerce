<x-layouts::backoffice :title="__('Dashboard')">
    <x-backoffice.layout.main-content-container :title="__('Dashboard')" :subtitle="__('Welcome back, :name. Here is what is happening today.', ['name' => $user->name])">
        @if ($stats)
            <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,14rem),1fr))] gap-4">
                @foreach ($stats as $stat)
                    <x-backoffice.dashboard.stat-card :icon="$stat['icon']" :label="$stat['label']" :value="$stat['value']"
                        :trend="$stat['trend']" :trend-up="$stat['trendUp']" />
                @endforeach
            </div>
        @else
            <flux:callout icon="information-circle" :heading="__('Nothing to show yet')"
                :text="__('Ask an admin to grant you access to a backoffice area.')" />
        @endif

        @isset($panels['orders'])
            <x-backoffice.dashboard.panel :title="__('Orders')" :description="__('Customer orders that need your attention')">
                <x-slot:actions>
                    <flux:button size="sm" variant="ghost" icon-trailing="arrow-right"
                        :href="route('manage.orders.index')" wire:navigate>{{ __('Manage orders') }}</flux:button>
                </x-slot:actions>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div class="min-w-0 space-y-5">
                        <x-backoffice.dashboard.pipeline :total="$panels['orders']['pipeline']['total']" :label="__('orders this month')" :stages="$panels['orders']['pipeline']['stages']" />

                        @if ($panels['orders']['pipeline']['pending'])
                            <flux:callout variant="warning" icon="exclamation-triangle"
                                :heading="trans_choice(':count open order|:count open orders', $panels['orders']['pipeline'][
                                    'pending'
                                ], ['count' => $panels['orders']['pipeline']['pending']])"
                                :text="__('Contact those customers and confirm their payment.')" />
                        @endif
                    </div>

                    <div
                        class="min-w-0 divide-y divide-zinc-200 rounded-xl shadow-card border border-zinc-200/80 bg-white lg:col-span-2 dark:divide-zinc-800 dark:border-zinc-800 dark:bg-zinc-950">
                        @forelse ($panels['orders']['recent'] as $order)
                            <div class="flex items-center gap-3 px-3 py-3 sm:px-4">
                                <flux:avatar size="sm" class="max-sm:hidden" :name="$order['email']" />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-zinc-900 dark:text-white">
                                        {{ $order['email'] }}</p>
                                    <p class="truncate font-mono text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $order['order_id'] }} · {{ $order['time'] }}</p>
                                </div>
                                <span
                                    class="shrink-0 text-sm font-medium text-zinc-900 dark:text-white">{{ $order['amount'] }}</span>
                                <flux:badge size="sm" :color="$order['payment_color']">{{ $order['payment'] }}
                                </flux:badge>
                            </div>
                        @empty
                            <flux:text class="px-4 py-6 text-center">{{ __('No orders yet.') }}</flux:text>
                        @endforelse
                    </div>
                </div>
            </x-backoffice.dashboard.panel>
        @endisset

        <div class="flex flex-col gap-4 lg:flex-row">
            @isset($panels['registrations'])
                <x-backoffice.dashboard.panel class="min-w-0 lg:flex-[2]" :title="__('Recent registrations')" :description="__('Newest customers in the shop')">
                    <x-slot:actions>
                        <flux:button size="sm" variant="ghost" icon-trailing="arrow-right"
                            :href="route('manage.users.index')" wire:navigate>{{ __('View all') }}</flux:button>
                    </x-slot:actions>

                    <div class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @forelse ($panels['registrations'] as $customer)
                            <div class="flex items-center gap-3 py-3">
                                <flux:avatar size="sm" :name="$customer['name']" />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-zinc-900 dark:text-white">
                                        {{ $customer['name'] }}</p>
                                    <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $customer['email'] }}
                                    </p>
                                </div>
                                <span
                                    class="hidden text-xs text-zinc-500 sm:block dark:text-zinc-400">{{ $customer['joined'] }}</span>
                                <flux:badge size="sm" :color="$customer['status_color']">{{ $customer['status'] }}
                                </flux:badge>
                            </div>
                        @empty
                            <flux:text class="py-6 text-center">{{ __('No customers yet.') }}</flux:text>
                        @endforelse
                    </div>
                </x-backoffice.dashboard.panel>
            @endisset

            @isset($panels['inventory'])
                <x-backoffice.dashboard.panel class="min-w-0 lg:flex-1" :title="__('Book inventory')" :description="__('Availability across the catalogue')">
                    <div class="space-y-5">
                        @foreach ($panels['inventory'] as $item)
                            <x-backoffice.dashboard.progress-item :label="$item['label']" :value="$item['value']"
                                :percent="$item['percent']" />
                        @endforeach
                    </div>
                </x-backoffice.dashboard.panel>
            @endisset
        </div>
    </x-backoffice.layout.main-content-container>
</x-layouts::backoffice>
