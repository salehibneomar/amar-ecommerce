<x-layouts::frontend :title="__('Refund policy')">
    <x-frontend.layout.page :title="__('Refund and order policy')" :subtitle="__('Last updated: 14 October 2026')" width="max-w-3xl">
        <x-frontend.cards.card>
            <x-frontend.content.legal-section :title="__('How an order works')">
                <ol>
                    <li>{{ __('Add books to your cart and go to checkout.') }}</li>
                    <li>{{ __('Place your order and you get a unique order ID. Contact us with it on WhatsApp, Messenger or email and pay with bKash or Nagad.') }}</li>
                    <li>{{ __('We check your payment against our statement. Once verified, your books unlock in My library and your invoice is ready.') }}</li>
                    <li>{{ __('Printed books are packed and shipped to the address you entered. We call you before delivery.') }}</li>
                </ol>
            </x-frontend.content.legal-section>
        </x-frontend.cards.card>

        <x-frontend.cards.card>
            <x-frontend.content.legal-section :title="__('Payment verification')">
                <p>{{ __('Every payment is checked by a person on our team. If we cannot find your TrxID, or the amount does not match your order, we mark the payment as failed and you can contact us to correct it.') }}</p>
                <p>{{ __('Books unlock only after your payment is verified.') }}</p>
            </x-frontend.content.legal-section>
        </x-frontend.cards.card>

        <x-frontend.cards.card>
            <x-frontend.content.legal-section :title="__('Offers and discounts')">
                <p>{{ __('Book discounts and shop-wide offers are applied automatically at checkout. The price you see when you place the order is the price you pay, even if the offer ends later.') }}</p>
            </x-frontend.content.legal-section>
        </x-frontend.cards.card>

        <x-frontend.cards.card>
            <x-frontend.content.legal-section :title="__('Cancellations')">
                <ul>
                    <li>{{ __('We may cancel an order if the payment cannot be verified.') }}</li>
                    <li>{{ __('Printed book orders can be cancelled before they are shipped. Contact us with your order ID.') }}</li>
                    <li>{{ __('If you paid for an order that was cancelled, contact us with your TrxID and we will refund or match it to a new order.') }}</li>
                </ul>
            </x-frontend.content.legal-section>
        </x-frontend.cards.card>

        <x-frontend.cards.card>
            <x-frontend.content.legal-section :title="__('Refunds')">
                <p>{{ __('Digital books open instantly, so once your payment is verified the purchase is final and cannot be refunded.') }}</p>
                <p>{{ __('Printed books that arrive damaged or wrong can be replaced. Tell us within 3 days of delivery.') }}</p>
                <p>{{ __('If you sent the wrong amount or paid twice, contact us with your order ID and TrxID and we will sort it out with you.') }}</p>
            </x-frontend.content.legal-section>
        </x-frontend.cards.card>

        <x-frontend.cards.card>
            <x-frontend.content.legal-section :title="__('Questions')">
                <p>{{ __('For anything about your order or payment, reach us from the contact page. We reply every day from 9:00 AM to 11:00 PM (Bangladesh time).') }}</p>
                <flux:button size="sm" variant="outline" :href="route('contact')" wire:navigate>{{ __('Contact us') }}</flux:button>
            </x-frontend.content.legal-section>
        </x-frontend.cards.card>
    </x-frontend.layout.page>
</x-layouts::frontend>
