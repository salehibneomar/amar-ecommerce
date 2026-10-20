@props(['order', 'contactLinks' => [], 'payUrl' => null])

<x-ui.card {{ $attributes->class('p-5') }}>
    <x-ui.alert :color="$order->status->color()" :icon="$order->status->icon()"
        :heading="$order->status->notice()['heading']" :text="$order->status->notice()['text']" />

    @unless ($order->status->isCancelled())
        <x-backoffice.orders.status-steps :steps="$order->status->steps()" class="mt-5" />

        @if ($payUrl)
            <x-ui.banner tone="accent" size="none" class="mt-5 flex flex-wrap items-center justify-between gap-3 rounded-lg p-4">
                <p class="text-sm text-zinc-700 dark:text-zinc-200">
                    {{ $order->payment_status->isFailed() ? __('Your last payment did not go through. You can try again.') : __('Your order is waiting for payment.') }}
                </p>
                <x-ui.button size="sm" variant="primary" icon="credit-card" :href="$payUrl">{{ __('Pay :amount', ['amount' => formatMoney($order->total)]) }}</x-ui.button>
            </x-ui.banner>
        @elseif (count($contactLinks))
            <x-ui.banner tone="accent" size="none" class="mt-5 space-y-3 rounded-lg p-4">
                <p class="text-sm text-zinc-700 dark:text-zinc-200">
                    {{ __('Contact us with your order ID. We share the bKash or Nagad payment details and confirm the payment.') }}
                </p>
                <x-frontend.content.contact-links :links="$contactLinks" />
            </x-ui.banner>
        @elseif ($order->payment_status->isFailed())
            <x-ui.alert class="mt-5" icon="exclamation-triangle" variant="danger"
                :heading="__('We could not verify your payment')"
                :text="__('Please contact us with your transaction ID so we can help.')" />
        @endif
    @endunless
</x-ui.card>
