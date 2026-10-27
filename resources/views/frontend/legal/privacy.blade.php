<x-layouts.frontend :title="__('Privacy')">
    <x-frontend.layout.page :title="__('Privacy policy')">
        <x-frontend.cards.card>
            <x-frontend.content.legal-section :title="__('What we collect')">
                <ul>
                    <li>{{ __('Account details: your name, email address and a securely hashed password.') }}</li>
                    <li>{{ __('If you sign in with Google or Facebook: your name and email address from that account.') }}</li>
                    <li>{{ __('Order details: your order ID, the products, your phone number, delivery address and payment details.') }}</li>
                    <li>{{ __('Basic technical data such as your IP address and session cookies, used to keep you logged in and protect the site from abuse.') }}</li>
                </ul>
            </x-frontend.content.legal-section>
        </x-frontend.cards.card>

        <x-frontend.cards.card>
            <x-frontend.content.legal-section :title="__('How we use it')">
                <ul>
                    <li>{{ __('To create your account and process and deliver your orders.') }}</li>
                    <li>{{ __('To confirm your payments and send you emails about your account and orders.') }}</li>
                    <li>{{ __('To answer your questions when you contact us.') }}</li>
                </ul>
                <p>{{ __('We do not sell your data and we do not send you marketing emails.') }}</p>
            </x-frontend.content.legal-section>
        </x-frontend.cards.card>

        <x-frontend.cards.card>
            <x-frontend.content.legal-section :title="__('Who we share it with')">
                <p>{{ __('We share data only with the services we need to run the platform: our hosting provider, our email provider, Google or Facebook if you choose to sign in with them, and the delivery partner that brings your order. Payment providers handle your payment directly and we never see your PIN or card details.') }}</p>
            </x-frontend.content.legal-section>
        </x-frontend.cards.card>

        <x-frontend.cards.card>
            <x-frontend.content.legal-section :title="__('Your choices')">
                <p>{{ __('You can update your name, email and password from your account settings. To delete your account and personal data, contact us from the email address on your account. We keep order and payment records as long as we need them for our accounts.') }}</p>
            </x-frontend.content.legal-section>
        </x-frontend.cards.card>

        <x-frontend.cards.card>
            <x-frontend.content.legal-section :title="__('Contact')">
                <p>{{ __('Questions about your privacy? Reach us from the contact page.') }}</p>
                <x-ui.button size="sm" variant="outline" :href="route('contact')" wire:navigate>{{ __('Contact Us') }}</x-ui.button>
            </x-frontend.content.legal-section>
        </x-frontend.cards.card>
    </x-frontend.layout.page>
</x-layouts.frontend>
