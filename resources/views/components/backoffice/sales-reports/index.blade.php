<x-backoffice.layout.main-content-container :title="__('Sales Reports')" :subtitle="__('Paid orders only. Pending and failed payments are not counted.')">
    <div class="flex flex-wrap items-end gap-3">
        <flux:select wire:model.live="month" :label="__('Month')" class="max-w-44">
            @foreach ($this->monthOptions as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="year" :label="__('Year')" class="max-w-32">
            @foreach ($this->yearOptions as $value)
                <flux:select.option :value="$value">{{ $value }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:icon.arrow-path class="mb-2 size-5 animate-spin text-zinc-400" wire:loading />
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <x-backoffice.dashboard.panel :title="__('Summary')" :description="__('Paid sales in the selected month')">
            <div class="divide-y divide-line">
                @foreach ([
                    ['banknotes', __('Revenue'), formatMoney($this->summary['revenue'])],
                    ['shopping-bag', __('Paid orders'), number_format($this->summary['orders'])],
                    ['book-open', __('Books sold'), number_format($this->summary['items'])],
                    ['calculator', __('Average order'), formatMoney($this->summary['average'])],
                    ['receipt-percent', __('Offer discount given'), formatMoney($this->summary['discount'])],
                ] as [$icon, $label, $value])
                    <div class="flex items-center gap-3 py-3 text-sm">
                        <flux:icon :icon="$icon" variant="outline" class="size-5 text-zinc-400" />
                        <span class="flex-1 text-zinc-600 dark:text-zinc-300">{{ $label }}</span>
                        <span class="font-semibold text-zinc-900 dark:text-white">{{ $value }}</span>
                    </div>
                @endforeach
            </div>
        </x-backoffice.dashboard.panel>

        <x-backoffice.dashboard.panel :title="__('Revenue in :year', ['year' => $year])" :description="__('Monthly paid revenue')">
            <div class="flex h-48 min-w-0 items-end gap-0.5 sm:gap-1.5">
                @foreach ($this->monthlyRevenue as $index => $item)
                    <div class="flex h-full min-w-0 flex-1 flex-col items-center justify-end gap-1" title="{{ formatMoney($item['revenue']) }}">
                        <div @class([
                            'w-full rounded-t-md border border-b-0 transition-all',
                            'border-tint-line bg-tint' => $index + 1 === $month,
                            'border-line bg-subtle' => $index + 1 !== $month,
                        ]) style="height: {{ max($item['percent'], 2) }}%"></div>
                        <span @class(['text-[10px]', 'font-semibold text-brand-text' => $index + 1 === $month, 'text-zinc-500' => $index + 1 !== $month])>{{ $item['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </x-backoffice.dashboard.panel>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <x-backoffice.dashboard.panel :title="__('Top books')" :description="__('Best sellers this month')">
            <div class="divide-y divide-line">
                @forelse ($this->topProducts as $product)
                    <div class="flex items-center gap-3 py-2.5 text-sm">
                        <x-ui.badge color="amber" circle class="shrink-0">{{ $loop->iteration }}</x-ui.badge>
                        <span class="min-w-0 flex-1 truncate font-medium text-zinc-900 dark:text-white">{{ $product['name'] }}</span>
                        <span class="shrink-0 whitespace-nowrap text-zinc-500">{{ trans_choice(':count copy|:count copies', $product['quantity'], ['count' => $product['quantity']]) }}</span>
                        <span class="w-20 shrink-0 text-end font-medium">{{ formatMoney($product['revenue']) }}</span>
                    </div>
                @empty
                    <flux:text class="py-6 text-center">{{ __('No sales in this month.') }}</flux:text>
                @endforelse
            </div>
        </x-backoffice.dashboard.panel>

        <x-backoffice.dashboard.panel :title="__('Daily sales')" :description="__('Paid orders per day')">
            <div class="max-h-96 divide-y divide-line overflow-y-auto">
                @forelse ($this->dailySales as $day)
                    <div class="flex items-center justify-between gap-3 py-2.5 text-sm">
                        <span class="min-w-0 truncate text-zinc-700 dark:text-zinc-300">{{ $day['date'] }}</span>
                        <span class="shrink-0 whitespace-nowrap text-zinc-500">{{ trans_choice(':count order|:count orders', $day['orders'], ['count' => $day['orders']]) }}</span>
                        <span class="w-20 shrink-0 text-end font-medium">{{ formatMoney($day['revenue']) }}</span>
                    </div>
                @empty
                    <flux:text class="py-6 text-center">{{ __('No sales in this month.') }}</flux:text>
                @endforelse
            </div>
        </x-backoffice.dashboard.panel>
    </div>
</x-backoffice.layout.main-content-container>
