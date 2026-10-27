<x-mail::message>
# {{ __('Your order is being processed') }}

{{ __('Hi :name,', ['name' => $order->customer_name]) }}

{{ __('We have confirmed your order and are preparing it. We will let you know as soon as it ships.') }}

<x-mail.order-panel :order="$order" />

<x-mail::button :url="route('orders.track', ['order' => $order->public_order_id])">
{{ __('Track your order') }}
</x-mail::button>

{{ __('Thanks for your patience,') }}<br>
{{ config('app.name') }}
</x-mail::message>
