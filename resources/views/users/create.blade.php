<x-app-layout>
    <x-slot name="header">Add User</x-slot>

    <x-item-modal-form
        name="user-create"
        title="Add User"
        action="{{ route('users.store') }}"
        submitLabel="Save User"
        mode="page"
        cancelUrl="{{ route('users.index') }}"
    >
        @include('users._form')
    </x-item-modal-form>
</x-app-layout>
