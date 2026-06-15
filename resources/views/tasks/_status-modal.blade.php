@php($selectedTask = $selectedTask ?? null)
@php($statusValue = old('status', $selectedTask?->status ?: 'open'))

<x-item-modal-form
    name="task-status-modal"
    title="Update Status"
    action="{{ $selectedTask ? route('tasks.status.update', $selectedTask) : '' }}"
    submitLabel="Save Status"
    method="PATCH"
    mode="modal"
    :show="$showModal ?? false"
    maxWidth="lg"
>
    <div class="row g-3">
        <div class="col-12">
            <div class="surface-card p-3">
                <div class="section-kicker mb-1">Selected task</div>
                <div class="fw-semibold" data-task-status-task-title>
                    {{ $selectedTask?->title ?: 'Choose a task status from the table.' }}
                </div>
                <div class="small text-muted" data-task-status-task-client>
                    {{ $selectedTask?->client?->name ?: 'The modal will populate from the row you choose.' }}
                </div>
                <div class="mt-2">
                    <span
                        class="badge {{ $selectedTask?->statusBadgeClass() ?: 'badge-soft' }}"
                        data-task-status-task-badge
                    >
                        {{ $selectedTask?->statusLabel() ?: 'Not selected' }}
                    </span>
                </div>
            </div>
        </div>

        <input
            type="hidden"
            name="task_id"
            value="{{ old('task_id', $selectedTask?->id) }}"
            data-task-status-task-id
        >

        <div class="col-12">
            <label class="form-label">Status</label>
            <select class="form-select @error('status') is-invalid @enderror" name="status" required data-task-status-select>
                @foreach (\App\Models\Task::statusOptions() as $value => $label)
                    <option value="{{ $value }}" @selected($statusValue === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
    </div>
</x-item-modal-form>
