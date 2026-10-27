@props(['order'])

<x-mail::panel>
**{{ __('Order ID') }}:** {{ $order->public_order_id }}<br>
@foreach ($order->items as $item)
{{ $item->product_name_snapshot }} × {{ $item->quantity }} · @if ($item->original_total > $item->line_total)<s>{{ formatMoney($item->original_total) }}</s> @endif{{ formatMoney($item->line_total) }}@if ($item->discount_percentage) ({{ __(':percent% off', ['percent' => $item->discount_percentage]) }})@endif<br>
@endforeach
@if ($order->extra_discount_amount > 0)
{{ __('Extra discount (:percent%)', ['percent' => $order->extra_discount_percentage]) }} · − {{ formatMoney($order->extra_discount_amount) }}<br>
@endif
**{{ __('Total') }}:** {{ formatMoney($order->total) }}
</x-mail::panel>
