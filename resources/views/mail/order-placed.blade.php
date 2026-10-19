<x-mail::message>
# {{ __('Thank you for your order, :name!', ['name' => $order->customer_name]) }}

{{ __('We have received your order. To complete it, contact us on WhatsApp, Messenger or email with your order ID. We will share the bKash or Nagad payment details, confirm your payment and unlock your books.') }}

@include('mail.partials.order-panel')

<x-mail::button :url="route('orders.track', ['order' => $order->public_order_id])">
{{ __('Track your order') }}
</x-mail::button>

@if ($order->user_id === null)
{{ __('To read your books after payment, create a free account with this same email address. Your order is added to it as soon as you verify the email.') }}

<x-mail::button :url="route('user.register')" color="success">
{{ __('Create your account') }}
</x-mail::button>
@endif

{{ __('Thanks,') }}<br>
{{ config('app.name') }}
</x-mail::message>
