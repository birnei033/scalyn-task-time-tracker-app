@php($selectedTask = $selectedTask ?? null)
@php($showUserSelect = auth()->user()->canManageTeam())
@php($statusValue = old('status', $selectedTask?->status ?: 'open'))
@php($returnTo = old('return_to', request()->fullUrl() . (request()->routeIs('tasks.show') ? '#logged-time-pane' : '')))
@php($minutesValue = old('minutes'))

<x-item-modal-form
    name="task-log-time-modal"
    title="Log Time"
    action="{{ route('tasks.log-time') }}"
    submitLabel="Save Entry"
    mode="modal"
    :show="$showModal ?? false"
    maxWidth="lg"
>
    <div class="row g-3">
        <div class="col-12">
            <div class="surface-card p-3">
                <div class="section-kicker mb-1">Selected task</div>
                <div class="fw-semibold" data-task-log-time-task-title>
                    {{ $selectedTask?->title ?: 'Choose a task from the table to log time.' }}
                </div>
                <div class="small text-muted" data-task-log-time-task-client>
                    {{ $selectedTask?->client?->name ?: 'The popup will populate from the row you choose.' }}
                </div>
                <div class="mt-2">
                    <span
                        class="badge {{ $selectedTask?->statusBadgeClass() ?: 'badge-soft' }}"
                        data-task-log-time-task-status-badge
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
            data-task-log-time-task-id
        >
        <input type="hidden" name="return_to" value="{{ $returnTo }}">

        @if ($showUserSelect)
            <div class="col-lg-6">
                <label class="form-label">User <x-required-indicator /></label>
                <select class="form-select @error('user_id') is-invalid @enderror" name="user_id" required data-task-log-time-user>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected(old('user_id', auth()->id()) == $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
                @error('user_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
        @else
            <input type="hidden" name="user_id" value="{{ old('user_id', auth()->id()) }}" data-task-log-time-user>
        @endif

        <div class="col-lg-6">
            <label class="form-label">Status <x-required-indicator /></label>
            <select class="form-select @error('status') is-invalid @enderror" name="status" required data-task-log-time-status>
                @foreach (\App\Models\Task::statusOptions() as $value => $label)
                    <option value="{{ $value }}" @selected($statusValue === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>

        <div class="col-lg-4">
            <label class="form-label">Date <x-required-indicator /></label>
            <input
                type="date"
                class="form-control @error('date') is-invalid @enderror"
                name="date"
                value="{{ old('date', now()->toDateString()) }}"
                required
                data-task-log-time-date
            >
            @error('date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>

        <div class="col-lg-4">
            <label class="form-label">Minutes <x-required-indicator /></label>
            <input
                type="number"
                min="1"
                max="1440"
                step="1"
                class="form-control @error('minutes') is-invalid @enderror"
                name="minutes"
                value="{{ $minutesValue }}"
                required
                data-task-log-time-minutes
            >
            @error('minutes')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>

        <div class="col-12">
            <x-rich-text-editor
                name="notes"
                label="Work Description / Notes"
                :value="old('notes')"
                placeholder="Describe the work, updates, blockers, or context."
                :rows="5"
                id="task-log-time-notes"
            />
        </div>
    </div>
</x-item-modal-form>
