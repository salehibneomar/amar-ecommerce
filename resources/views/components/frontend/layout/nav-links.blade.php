@props([
    'landing' => false,
    'variant' => 'inline',
])

<x-frontend.layout.nav-link :variant="$variant" :href="route('home')" :current="request()->routeIs('home')" wire:navigate>{{ __('Home') }}</x-frontend.layout.nav-link>
<x-frontend.layout.nav-link :variant="$variant" :href="route('shop')" :current="request()->routeIs('shop*')" wire:navigate>{{ __('Shop') }}</x-frontend.layout.nav-link>
<x-frontend.layout.nav-link :variant="$variant" :href="route('faq')" :current="request()->routeIs('faq')" wire:navigate>{{ __('FAQ') }}</x-frontend.layout.nav-link>
<x-frontend.layout.nav-link :variant="$variant" :href="route('about')" :current="request()->routeIs('about')" wire:navigate>{{ __('About') }}</x-frontend.layout.nav-link>
<x-frontend.layout.nav-link :variant="$variant" :href="route('contact')" :current="request()->routeIs('contact')" wire:navigate>{{ __('Contact') }}</x-frontend.layout.nav-link>
<x-frontend.layout.nav-link :variant="$variant" :href="route('orders.track')" :current="request()->routeIs('orders.track')" wire:navigate>{{ __('Track Order') }}</x-frontend.layout.nav-link>
@if (isAiAssistantEnabled())
    <x-frontend.layout.nav-link :variant="$variant" :href="route('assistant')" :current="request()->routeIs('assistant')" :class="$variant === 'inline' ? 'inline-flex items-center gap-1.5' : 'gap-1.5'" wire:navigate>
        {{ __('Ask AI') }}
        <span class="material-symbols-outlined text-lg! leading-none">auto_awesome</span>
    </x-frontend.layout.nav-link>
@endif
