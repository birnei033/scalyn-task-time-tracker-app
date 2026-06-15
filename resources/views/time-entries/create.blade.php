<x-app-layout>
    <x-slot name="header">Add Time Entry</x-slot>
    @php($contextTask = request('task_id') ? $tasks->firstWhere('id', (int) request('task_id')) : null)

    <x-item-modal-form
        name="time-entry-create"
        title="Add Time Entry"
        action="{{ route('time-entries.store') }}"
        submitLabel="Save Entry"
        mode="page"
        cancelUrl="{{ route('time-entries.index') }}"
    >
        @include('time-entries._form')
    </x-item-modal-form>
</x-app-layout>
