@php($selectedEntry = $selectedEntry ?? null)
@php($showModal = $showModal ?? false)
@php($returnTo = $returnTo ?? route('timesheets.index'))

<x-item-modal-form
    name="time-entry-edit-modal"
    title="Edit Time Entry"
    action="{{ $selectedEntry ? route('time-entries.update', $selectedEntry) : '' }}"
    submitLabel="Update Entry"
    method="PUT"
    mode="modal"
    :show="$showModal"
    maxWidth="lg"
>
    <input type="hidden" name="return_to" value="{{ $returnTo }}">
    <input type="hidden" name="editing_entry" value="{{ old('editing_entry', $selectedEntry?->id) }}">

@include('time-entries._form', [
    'entry' => $selectedEntry ?? new \App\Models\TimeEntry,
    'tasks' => $tasks,
    'users' => $users,
    'contextTask' => $contextTask ?? null,
])
</x-item-modal-form>
