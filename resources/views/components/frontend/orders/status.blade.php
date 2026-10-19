@props(['order', 'contactLinks' => []])

<div
    {{ $attributes->class(['rounded-2xl shadow-card border border-zinc-200/80 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900']) }}>
    <flux:callout :color="$order->status->color()" :icon="$order->status->icon()"
        :heading="$order->status->notice()['heading']" :text="$order->status->notice()['text']" class="border-0" />

    @unless ($order->status->isCancelled())
        <x-backoffice.orders.status-steps :steps="$order->status->steps()" class="mt-5" />

        @if ($order->status->isUnresolved())
            <div class="mt-5 space-y-3 rounded-xl bg-gold-50 p-4 dark:bg-gold-900/20">
                <p class="text-sm text-gold-900 dark:text-gold-200">
                    {{ __('Contact us with your order ID. We share the bKash or Nagad payment details, confirm the payment and unlock your books.') }}
                </p>
                @if (count($contactLinks))
                    <x-frontend.content.contact-links :links="$contactLinks" />
                @endif
            </div>
        @elseif ($order->payment_status->isNotPaid())
            <flux:callout class="mt-5 border-0" icon="exclamation-triangle" variant="danger"
                :heading="__('We could not verify your payment')"
                :text="__('Please contact us with your transaction ID so we can help.')" />
        @endif
    @endunless
</div>
