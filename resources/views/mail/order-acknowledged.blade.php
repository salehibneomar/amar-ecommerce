<x-mail::message>
# {{ __('Your order is being processed') }}

{{ __('Hi :name,', ['name' => $order->customer_name]) }}

{{ __('We have acknowledged your order and are working on it. As soon as your payment is verified, your books unlock in My library and we will let you know.') }}

@include('mail.partials.order-panel')

<x-mail::button :url="route('orders.track', ['order' => $order->public_order_id])">
{{ __('Track your order') }}
</x-mail::button>

{{ __('Thanks for your patience,') }}<br>
{{ config('app.name') }}
</x-mail::message>
