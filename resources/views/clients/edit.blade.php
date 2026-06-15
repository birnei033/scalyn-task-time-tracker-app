<x-app-layout>
    <x-slot name="header">Edit Client</x-slot>

    <x-item-modal-form
        name="client-edit-{{ $client->id }}"
        title="Edit Client"
        action="{{ route('clients.update', $client) }}"
        submitLabel="Update Client"
        method="PUT"
        mode="page"
        cancelUrl="{{ route('clients.index') }}"
    >
        @include('clients._form')
    </x-item-modal-form>
</x-app-layout>
