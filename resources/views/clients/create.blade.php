<x-app-layout>
    <x-slot name="header">Add Client</x-slot>

    <x-item-modal-form
        name="client-create"
        title="Add Client"
        action="{{ route('clients.store') }}"
        submitLabel="Save Client"
        mode="page"
        cancelUrl="{{ route('clients.index') }}"
    >
        @include('clients._form')
    </x-item-modal-form>
</x-app-layout>
