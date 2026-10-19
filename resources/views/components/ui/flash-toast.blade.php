@persist('toast')
    <flux:toast.group>
        <flux:toast />
    </flux:toast.group>
@endpersist

@if (session('success') || session('warning') || session('info') || session('error'))
    <div
        x-data
        x-init="$nextTick(() => $dispatch('toast-show', {
            slots: { text: @js(session('success') ?? session('warning') ?? session('info') ?? session('error')) },
            dataset: { variant: @js(session('success') ? 'success' : (session('warning') ? 'warning' : (session('info') ? 'info' : 'danger'))) },
        }))"
    ></div>
@endif
