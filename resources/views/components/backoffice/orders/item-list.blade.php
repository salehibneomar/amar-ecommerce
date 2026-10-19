@props(['order'])

<div {{ $attributes->class(['divide-y divide-line rounded-lg border border-line']) }}>
    @foreach ($order->items as $item)
        <div wire:key="order-item-{{ $item->id }}" class="flex items-center justify-between gap-3 px-3 py-2 text-sm">
            <div class="min-w-0">
                <p class="truncate font-medium text-zinc-900 dark:text-white">{{ $item->product_name_snapshot }}</p>
                <p class="text-xs text-zinc-500">
                    {{ $item->quantity }} × {{ formatMoney($item->unit_price) }}
                    @if ($item->discount_percentage)
                        · {{ __(':percent% off', ['percent' => $item->discount_percentage]) }}
                    @endif
                    @if ($item->offer_name_snapshot)
                        · {{ __(':name (:percent% off)', ['name' => $item->offer_name_snapshot, 'percent' => $item->offer_discount_percentage]) }}
                    @endif
                </p>
            </div>
            <span class="font-medium">{{ formatMoney($item->line_total) }}</span>
        </div>
    @endforeach
    <div class="space-y-1 bg-subtle px-3 py-2 text-sm">
        <div class="flex justify-between text-zinc-500"><span>{{ __('Subtotal') }}</span><span>{{ formatMoney($order->subtotal) }}</span></div>
        @forelse ($order->applied_offers ?? [] as $offer)
            <div class="flex justify-between text-zinc-500"><span>{{ __(':name (:percent% off)', ['name' => $offer['name'], 'percent' => $offer['percentage']]) }}</span><span>− {{ formatMoney($offer['amount']) }}</span></div>
        @empty
            @if ($order->discount_amount > 0)
                <div class="flex justify-between text-zinc-500"><span>{{ __('Offer discount') }}</span><span>− {{ formatMoney($order->discount_amount) }}</span></div>
            @endif
        @endforelse
        @if ($order->extra_discount_amount > 0)
            <div class="flex justify-between text-zinc-500"><span>{{ __('Extra discount (:percent%)', ['percent' => $order->extra_discount_percentage]) }}</span><span>− {{ formatMoney($order->extra_discount_amount) }}</span></div>
        @endif
        <div class="flex justify-between font-semibold text-zinc-900 dark:text-white"><span>{{ __('Total') }}</span><span>{{ formatMoney($order->total) }}</span></div>
    </div>
</div>
