<x-layouts::frontend :title="__('Terms')">
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
            <x-frontend.content.legal-section :title="__('Free preview and purchases')">
                <p>{{ __('Anyone can read the free preview of each book. When you buy a digital book it stays in your library for as long as the platform runs. Orders and payments follow our refund and order policy.') }}</p>
            </x-frontend.content.legal-section>
        </x-frontend.cards.card>

        <x-frontend.cards.card>
            <x-frontend.content.legal-section :title="__('How you may use the books')">
                <p>{{ __('All books and PDFs are for your personal study only. You may not:') }}</p>
                <ul>
                    <li>{{ __('Share, upload or send the books or downloaded PDFs to anyone else.') }}</li>
                    <li>{{ __('Sell, reprint or copy the content for any business purpose.') }}</li>
                    <li>{{ __('Try to get around the free preview limits or access content you have not paid for.') }}</li>
                </ul>
                <p>{{ __('If you break these rules we may block your account without a refund.') }}</p>
            </x-frontend.content.legal-section>
        </x-frontend.cards.card>

        <x-frontend.cards.card>
            <x-frontend.content.legal-section :title="__('Our content')">
                <p>{{ __('We work hard to keep our books accurate and up to date, but farming conditions differ from place to place and we cannot promise any result. Use the books alongside advice from your local agriculture office.') }}</p>
            </x-frontend.content.legal-section>
        </x-frontend.cards.card>

        <x-frontend.cards.card>
            <x-frontend.content.legal-section :title="__('Changes to these terms')">
                <p>{{ __('We may update these terms from time to time. Continuing to use the platform after a change means you accept the updated terms.') }}</p>
            </x-frontend.content.legal-section>
        </x-frontend.cards.card>
    </x-frontend.layout.page>
</x-layouts::frontend>
