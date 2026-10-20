<x-mail::message>
# {{ __('Your order is on the way') }}

{{ __('Hi :name,', ['name' => $order->customer_name]) }}

{{ __('Your order has shipped and will reach :address soon.', ['address' => $order->shipping_address]) }}

@include('mail.partials.order-panel')

<x-mail::button :url="route('orders.track', ['order' => $order->public_order_id])">
{{ __('Track your order') }}
</x-mail::button>

{{ __('Thanks,') }}<br>
{{ config('app.name') }}
</x-mail::message>
