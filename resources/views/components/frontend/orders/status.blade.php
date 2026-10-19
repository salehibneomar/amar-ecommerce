@props(['order', 'contactLinks' => []])

<x-ui.card {{ $attributes->class('p-5') }}>
    <x-ui.alert :color="$order->status->color()" :icon="$order->status->icon()"
        :heading="$order->status->notice()['heading']" :text="$order->status->notice()['text']" />

    @unless ($order->status->isCancelled())
        <x-backoffice.orders.status-steps :steps="$order->status->steps()" class="mt-5" />

        @if ($order->status->isUnresolved())
            <x-ui.banner tone="accent" size="none" class="mt-5 space-y-3 rounded-lg p-4">
                <p class="text-sm text-zinc-700 dark:text-zinc-200">
                    {{ __('Contact us with your order ID. We share the bKash or Nagad payment details, confirm the payment and unlock your books.') }}
                </p>
                @if (count($contactLinks))
                    <x-frontend.content.contact-links :links="$contactLinks" />
                @endif
            </x-ui.banner>
        @elseif ($order->payment_status->isNotPaid())
            <x-ui.alert class="mt-5" icon="exclamation-triangle" variant="danger"
                :heading="__('We could not verify your payment')"
                :text="__('Please contact us with your transaction ID so we can help.')" />
        @endif
    @endunless
</x-ui.card>
