<x-layouts.backoffice :title="__('Payments of :name', ['name' => $member->name])">
    <livewire:backoffice.staff-payments.index :staff-id="$member->id" :staff-name="$member->name" />
</x-layouts.backoffice>
