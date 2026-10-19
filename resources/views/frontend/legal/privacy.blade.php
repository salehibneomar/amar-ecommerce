<x-layouts::frontend :title="__('Privacy')">
    <x-frontend.layout.page :title="__('Privacy policy')" :subtitle="__('Last updated: 14 October 2026')" width="max-w-3xl">
        <x-frontend.cards.card>
            <x-frontend.content.legal-section :title="__('What we collect')">
                <ul>
                    <li>{{ __('Account details: your name, email address and a securely hashed password.') }}</li>
                    <li>{{ __('If you sign in with Google or Facebook: your name and email address from that account.') }}</li>
                    <li>{{ __('Order details: your order ID, the package, the payment method and the transaction ID (TrxID) you send us.') }}</li>
                    <li>{{ __('Reading activity: which books you open and when, so we can show your recently opened books.') }}</li>
                    <li>{{ __('Basic technical data such as your IP address and session cookies, used to keep you logged in and protect the site from abuse.') }}</li>
                </ul>
            </x-frontend.content.legal-section>
        </x-frontend.cards.card>

        <x-frontend.cards.card>
            <x-frontend.content.legal-section :title="__('How we use it')">
                <ul>
                    <li>{{ __('To create your account and give you access to the books you are allowed to read.') }}</li>
                    <li>{{ __('To verify your payments and send you emails about your account and orders.') }}</li>
                    <li>{{ __('To answer your questions when you contact us.') }}</li>
                </ul>
                <p>{{ __('We do not sell your data and we do not send you marketing emails.') }}</p>
            </x-frontend.content.legal-section>
        </x-frontend.cards.card>

        <x-frontend.cards.card>
            <x-frontend.content.legal-section :title="__('Who we share it with')">
                <p>{{ __('We share data only with the services we need to run the platform: our hosting provider, our email provider, and Google or Facebook if you choose to sign in with them. Payments are made directly through bKash or Nagad, and we never see your mobile banking PIN.') }}</p>
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
                <flux:button size="sm" variant="outline" :href="route('contact')" wire:navigate>{{ __('Contact us') }}</flux:button>
            </x-frontend.content.legal-section>
        </x-frontend.cards.card>
    </x-frontend.layout.page>
</x-layouts::frontend>
