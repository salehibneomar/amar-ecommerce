<x-mail::message>
# {{ __('Your order was cancelled') }}

{{ __('Hi :name,', ['name' => $order->customer_name]) }}

{{ __('Your order has been cancelled. If you think this is a mistake or you already paid, please contact us with your order ID.') }}

@include('mail.partials.order-panel')

<x-mail::button :url="route('contact')">
{{ __('Contact us') }}
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
