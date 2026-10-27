<x-mail::message>
# {{ __('Thank you for your order, :name!', ['name' => $order->customer_name]) }}

@if ($isManualPayment)
{{ __('We have received your order. To complete it, contact us on WhatsApp, Messenger or email with your order ID. We will share the bKash or Nagad payment details and confirm your payment.') }}
@else
{{ __('We have received your order and will confirm it soon.') }}
@endif

<x-mail.order-panel :order="$order" />

<x-mail::button :url="route('orders.track', ['order' => $order->public_order_id])">
{{ __('Track your order') }}
</x-mail::button>

@if ($order->user_id === null)
{{ __('To follow your orders in one place, create a free account with this same email address. Your order is added to it as soon as you verify the email.') }}

<x-mail::button :url="route('user.register')" color="success">
{{ __('Create your account') }}
</x-mail::button>
@endif

{{ __('Thanks,') }}<br>
{{ config('app.name') }}
</x-mail::message>
