@php($selectedTask = $selectedTask ?? null)
@php($priorityValue = old('priority', $selectedTask?->priority ?: 'medium'))
@php($returnTo = old('return_to', request()->fullUrl()))

<x-item-modal-form
    name="task-priority-modal"
    title="Update Priority"
    action="{{ $selectedTask ? route('tasks.priority.update', $selectedTask) : '' }}"
    submitLabel="Save Priority"
    method="PATCH"
    mode="modal"
    :show="$showModal ?? false"
    maxWidth="lg"
>
    <div class="row g-3">
        <div class="col-12">
            <div class="surface-card p-3">
                <div class="section-kicker mb-1">Selected task</div>
                <div class="fw-semibold" data-task-priority-task-title>
                    {{ $selectedTask?->title ?: 'Choose a task priority from the table.' }}
                </div>
                <div class="small text-muted" data-task-priority-task-client>
                    {{ $selectedTask?->client?->name ?: 'The modal will populate from the row you choose.' }}
                </div>
                <div class="mt-2">
                    <span
                        class="badge {{ $selectedTask?->priorityBadgeClass() ?: 'badge-soft' }}"
                        data-task-priority-task-badge
                    >
                        {{ $selectedTask?->priorityLabel() ?: 'Not selected' }}
                    </span>
                </div>
            </div>
        </div>

        <input
            type="hidden"
            name="task_id"
            value="{{ old('task_id', $selectedTask?->id) }}"
            data-task-priority-task-id
        >
        <input type="hidden" name="return_to" value="{{ $returnTo }}" data-task-priority-return-to>

        <div class="col-12">
            <label class="form-label">Priority <x-required-indicator /></label>
            <select class="form-select @error('priority') is-invalid @enderror" name="priority" required data-task-priority-select>
                @foreach (['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'] as $value => $label)
                    <option value="{{ $value }}" @selected($priorityValue === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('priority')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
    </div>
</x-item-modal-form>
