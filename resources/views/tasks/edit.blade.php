<x-app-layout>
    <x-slot name="header">Edit Task</x-slot>

    <x-item-modal-form
        name="task-edit-{{ $task->id }}"
        title="Edit Task"
        action="{{ route('tasks.update', $task) }}"
        submitLabel="Update Task"
        method="PUT"
        mode="page"
        cancelUrl="{{ route('tasks.index') }}"
    >
        @include('tasks._form')
    </x-item-modal-form>
</x-app-layout>
