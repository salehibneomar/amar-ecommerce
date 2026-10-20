<x-layouts::frontend :title="__('Refund policy')">
    <x-frontend.layout.page :title="__('Refund and order policy')">
        <x-frontend.cards.card>
            <x-frontend.content.legal-section :title="__('How an order works')">
                <ol>
                    <li>{{ __('Add products to your cart and go to checkout.') }}</li>
                    <li>{{ __('Place your order and you get a unique order ID. Keep it to track the order or contact us.') }}</li>
                    <li>{{ __('We confirm the order, check the payment and prepare it for delivery.') }}</li>
                    <li>{{ __('Your order is packed and shipped to the address you entered. We call you before delivery.') }}</li>
                </ol>
            </x-frontend.content.legal-section>
        </x-frontend.cards.card>

        <x-frontend.cards.card>
            <x-frontend.content.legal-section :title="__('Payment verification')">
                <p>{{ __('Every payment is checked before the order is completed. If the payment cannot be confirmed, or the amount does not match your order, we mark it as failed and you can contact us to correct it.') }}</p>
                <p>{{ __('Your invoice is ready as soon as the payment is confirmed.') }}</p>
            </x-frontend.content.legal-section>
        </x-frontend.cards.card>

        <x-frontend.cards.card>
            <x-frontend.content.legal-section :title="__('Offers and discounts')">
                <p>{{ __('Product discounts and shop-wide offers are applied automatically at checkout. The price you see when you place the order is the price you pay, even if the offer ends later.') }}</p>
            </x-frontend.content.legal-section>
        </x-frontend.cards.card>

        <x-frontend.cards.card>
            <x-frontend.content.legal-section :title="__('Cancellations')">
                <ul>
                    <li>{{ __('We may cancel an order if the payment cannot be verified.') }}</li>
                    <li>{{ __('Orders can be cancelled before they are shipped. Contact us with your order ID.') }}</li>
                    <li>{{ __('If you paid for an order that is cancelled, we refund the payment first.') }}</li>
                </ul>
            </x-frontend.content.legal-section>
        </x-frontend.cards.card>

        <x-frontend.cards.card>
            <x-frontend.content.legal-section :title="__('Refunds')">
                <p>{{ __('Products that arrive damaged or wrong can be replaced or refunded. Tell us within 3 days of delivery.') }}</p>
                <p>{{ __('If you paid the wrong amount or paid twice, contact us with your order ID and we will sort it out with you.') }}</p>
            </x-frontend.content.legal-section>
        </x-frontend.cards.card>

        <x-frontend.cards.card>
            <x-frontend.content.legal-section :title="__('Questions')">
                <p>{{ __('For anything about your order or payment, reach us from the contact page. We reply every day from 9:00 AM to 11:00 PM (Bangladesh time).') }}</p>
                <x-ui.button size="sm" variant="outline" :href="route('contact')" wire:navigate>{{ __('Contact Us') }}</x-ui.button>
            </x-frontend.content.legal-section>
        </x-frontend.cards.card>
    </x-frontend.layout.page>
</x-layouts::frontend>
