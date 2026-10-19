<x-mail::message>
# {{ __('Your books are ready, :name!', ['name' => $order->customer_name]) }}

{{ __('Your payment has been verified and your order is complete. The books are unlocked in My library.') }}

@include('mail.partials.order-panel')

<x-mail::button :url="route('user.library')">
{{ __('Start reading') }}
</x-mail::button>

{{ __('Happy reading,') }}<br>
{{ config('app.name') }}
</x-mail::message>
