<x-app-layout>
    <x-slot name="header">Create Task</x-slot>

    <x-item-modal-form
        name="task-create"
        title="Create Task"
        action="{{ route('tasks.store') }}"
        submitLabel="Save Task"
        mode="page"
        cancelUrl="{{ route('tasks.index') }}"
    >
        @include('tasks._form')
    </x-item-modal-form>
</x-app-layout>
