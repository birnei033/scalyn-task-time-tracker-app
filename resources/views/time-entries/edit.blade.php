<x-app-layout>
    <x-slot name="header">Edit Time Entry</x-slot>

    <x-item-modal-form
        name="time-entry-edit-{{ $entry->id }}"
        title="Edit Time Entry"
        action="{{ route('time-entries.update', $entry) }}"
        submitLabel="Update Entry"
        method="PUT"
        mode="page"
        cancelUrl="{{ route('time-entries.index') }}"
    >
        @include('time-entries._form')
    </x-item-modal-form>
</x-app-layout>
