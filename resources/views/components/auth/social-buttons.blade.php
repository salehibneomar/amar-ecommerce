@props([
    'providers' => [],
    'separator' => __('Or continue with'),
])

@if ($providers)
    <div class="flex flex-col gap-4">
        <flux:separator :text="$separator" />

        <div @class(['grid gap-2', 'grid-cols-2' => count($providers) > 1])>
            @foreach ($providers as $provider)
                <x-ui.button variant="outline" color="surface" size="sm" class="w-full" :href="route('social.redirect', $provider)">
                    <x-slot:iconLeading>
                        <x-auth.provider-logo :provider="$provider->value" />
                    </x-slot:iconLeading>
                    {{ $provider->label() }}
                </x-ui.button>
            @endforeach
        </div>
    </div>
@endif
