<x-app-layout>
    <x-slot name="header">Edit User</x-slot>

    <x-item-modal-form
        name="user-edit-{{ $user->id }}"
        title="Edit User"
        action="{{ route('users.update', $user) }}"
        submitLabel="Update User"
        method="PUT"
        mode="page"
        cancelUrl="{{ route('users.index') }}"
    >
        @include('users._form')
    </x-item-modal-form>
</x-app-layout>
