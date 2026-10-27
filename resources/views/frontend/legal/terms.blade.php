<x-layouts.frontend :title="__('Terms')">
    <x-frontend.layout.page :title="__('Terms of use')">
        <x-frontend.cards.card>
            <x-frontend.content.legal-section :title="__('About these terms')">
                <p>{{ __('By creating an account or using :app you agree to these terms. If you do not agree, please do not use the platform.', ['app' => config('app.name')]) }}</p>
            </x-frontend.content.legal-section>
        </x-frontend.cards.card>

        <x-frontend.cards.card>
            <x-frontend.content.legal-section :title="__('Your account')">
                <ul>
                    <li>{{ __('Use your real name and an email address you can access. You must verify your email before you can place an order.') }}</li>
                    <li>{{ __('Keep your password safe. You are responsible for everything done with your account.') }}</li>
                    <li>{{ __('One account is for one person. Do not share your login with anyone.') }}</li>
                </ul>
            </x-frontend.content.legal-section>
        </x-frontend.cards.card>

        <x-frontend.cards.card>
            <x-frontend.content.legal-section :title="__('Orders and purchases')">
                <p>{{ __('Placing an order is an offer to buy. We confirm it once we check the order and the stock. Orders and payments follow our refund and order policy.') }}</p>
            </x-frontend.content.legal-section>
        </x-frontend.cards.card>

        <x-frontend.cards.card>
            <x-frontend.content.legal-section :title="__('Fair use')">
                <p>{{ __('Please use the shop honestly. You may not:') }}</p>
                <ul>
                    <li>{{ __('Place fake orders or orders you do not plan to receive.') }}</li>
                    <li>{{ __('Copy product photos or content for any business purpose.') }}</li>
                    <li>{{ __('Try to break, overload or misuse the platform.') }}</li>
                </ul>
                <p>{{ __('If you break these rules we may cancel your orders or block your account.') }}</p>
            </x-frontend.content.legal-section>
        </x-frontend.cards.card>

        <x-frontend.cards.card>
            <x-frontend.content.legal-section :title="__('Product information')">
                <p>{{ __('We work hard to keep product details, photos and prices accurate. Small differences in color or packaging can happen. Contact us if something does not match.') }}</p>
            </x-frontend.content.legal-section>
        </x-frontend.cards.card>

        <x-frontend.cards.card>
            <x-frontend.content.legal-section :title="__('Changes to these terms')">
                <p>{{ __('We may update these terms from time to time. Continuing to use the platform after a change means you accept the updated terms.') }}</p>
            </x-frontend.content.legal-section>
        </x-frontend.cards.card>
    </x-frontend.layout.page>
</x-layouts.frontend>
