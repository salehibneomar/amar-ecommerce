<x-mail::message>
# {{ __('New order') }}

{{ __(':name just placed an order. Please review and confirm it.', ['name' => $order->customer_name]) }}

@include('mail.partials.order-panel')

**{{ __('Email') }}:** {{ $order->customer_email }}<br>
**{{ __('Phone') }}:** {{ $order->phone }}

<x-mail::button :url="route('manage.orders.index', ['search' => $order->public_order_id])">
{{ __('Open orders') }}
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
