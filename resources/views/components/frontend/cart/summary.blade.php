@props([
    'summary',
    'delivery' => true,
])

<dl {{ $attributes->class(['space-y-2 text-sm']) }}>
    <div class="flex justify-between text-zinc-600 dark:text-zinc-300">
        <dt>{{ __('Subtotal') }}</dt>
        <dd>{{ formatMoney($summary['subtotal']) }}</dd>
    </div>
    @foreach ($summary['offers'] as $offer)
        <div class="flex justify-between gap-3 text-zinc-600 dark:text-zinc-300">
            <dt class="flex items-start gap-1">
                <span class="material-symbols-outlined text-base">local_offer</span>
                <span class="grid">
                    <span>{{ __(':name (:percent% off)', ['name' => $offer['name'], 'percent' => $offer['percentage']]) }}</span>
                    <x-frontend.content.offer-scope :offer="$offer" class="text-xs text-zinc-400 dark:text-zinc-500" />
                </span>
            </dt>
            <dd class="shrink-0">− {{ formatMoney($offer['amount']) }}</dd>
        </div>
    @endforeach
    @if (! $summary['offers'] && $summary['discount'] > 0)
        <div class="flex justify-between gap-3 text-zinc-600 dark:text-zinc-300">
            <dt>{{ __('Offer discount') }}</dt>
            <dd class="shrink-0">− {{ formatMoney($summary['discount']) }}</dd>
        </div>
    @endif
    @if (($summary['extraDiscount'] ?? 0) > 0)
        <div class="flex justify-between gap-3 text-zinc-600 dark:text-zinc-300">
            <dt>{{ __('Extra discount (:percent%)', ['percent' => $summary['extraDiscountPercentage']]) }}</dt>
            <dd class="shrink-0">− {{ formatMoney($summary['extraDiscount']) }}</dd>
        </div>
    @endif
    @if ($delivery)
        <div class="flex justify-between text-zinc-500 dark:text-zinc-400">
            <dt>{{ __('Delivery') }}</dt>
            <dd>{{ __('Confirmed by phone') }}</dd>
        </div>
    @endif
    <div class="flex justify-between border-t border-line pt-3 text-base font-semibold text-zinc-900 dark:text-white">
        <dt>{{ __('Total') }}</dt>
        <dd>{{ formatMoney($summary['total']) }}</dd>
    </div>
</dl>
