@persist('page-loader')
    <script>
        try {
            if (sessionStorage.getItem('page-loaded')) {
                document.documentElement.dataset.pageLoaded = '';
            }
        } catch (e) {}
    </script>

    <div
        x-data="{ visible: ! ('pageLoaded' in document.documentElement.dataset) && document.readyState !== 'complete' }"
        x-init="window.addEventListener('load', () => {
            visible = false;
            try { sessionStorage.setItem('page-loaded', '1'); } catch (e) {}
        })"
        x-show="visible"
        x-transition:leave.opacity.duration.300ms
        role="status"
        aria-live="polite"
        {{ $attributes->class(['fixed inset-0 z-100 flex flex-col items-center justify-center gap-4 bg-canvas [html[data-page-loaded]_&]:hidden']) }}
    >
        <flux:icon.loading class="size-10 text-brand-600" />
        <p class="text-sm text-zinc-500">{{ __('Loading...') }}</p>
    </div>
@endpersist
