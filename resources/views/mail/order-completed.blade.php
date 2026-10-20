<x-mail::message>
# {{ __('Your order is complete, :name!', ['name' => $order->customer_name]) }}

{{ __('Your order has been delivered and paid. We hope you enjoy it.') }}

@include('mail.partials.order-panel')

<x-mail::button :url="route('shop')">
{{ __('Shop again') }}
</x-mail::button>

{{ __('Thanks for shopping with us,') }}<br>
{{ config('app.name') }}
</x-mail::message>
