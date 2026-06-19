@php($timeEntry = $timeEntry ?? new \App\Models\TimeEntry)
@php($contextTask = $contextTask ?? null)
@php($showUserSelect = $showUserSelect ?? auth()->user()->canManageTeam())
@php($minutesValue = old('minutes', $timeEntry->exists ? \App\Support\TimeDisplay::hoursToMinutes($timeEntry->hours) : ''))

<div class="row g-3">
    @if ($showUserSelect)
        <div class="col-lg-6">
            <label class="form-label">User <x-required-indicator /></label>
            <select class="form-select @error('user_id') is-invalid @enderror" name="user_id" required>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected(old('user_id', $timeEntry->user_id ?: auth()->id()) == $user->id)>{{ $user->name }}</option>
                @endforeach
            </select>
            @error('user_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
    @else
        <input type="hidden" name="user_id" value="{{ old('user_id', $timeEntry->user_id ?: auth()->id()) }}">
    @endif

    <div class="{{ $contextTask ? 'col-12' : 'col-lg-6' }}">
        <label class="form-label">Task <x-required-indicator /></label>
        @if ($contextTask)
            <input type="hidden" name="task_id" value="{{ old('task_id', $timeEntry->task_id ?: $contextTask->id) }}">
            <div class="surface-card p-3">
                <div class="small text-muted">Time will be logged to this task</div>
                <div class="fw-semibold">{{ $contextTask->client->name }} - {{ $contextTask->title }}</div>
            </div>
        @else
            <select class="form-select @error('task_id') is-invalid @enderror" name="task_id" required>
                <option value="">Choose task</option>
                @foreach ($tasks as $task)
                    <option value="{{ $task->id }}" @selected(old('task_id', $timeEntry->task_id) == $task->id)>{{ $task->client->name }} - {{ $task->title }}</option>
                @endforeach
            </select>
            @error('task_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        @endif
    </div>

    <div class="col-lg-3">
        <label class="form-label">Date <x-required-indicator /></label>
        <input type="date" class="form-control @error('date') is-invalid @enderror" name="date" value="{{ old('date', $timeEntry->date?->toDateString() ?: now()->toDateString()) }}">
        @error('date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-lg-3">
        <label class="form-label">Minutes <x-required-indicator /></label>
        <input type="number" min="1" max="480" step="1" class="form-control @error('minutes') is-invalid @enderror" name="minutes" value="{{ $minutesValue }}">
        @error('minutes')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <x-rich-text-editor
            name="notes"
            label="Work Description / Notes"
            :value="$timeEntry->notes"
            placeholder="Describe the work, updates, blockers, or context."
            :rows="5"
            id="time-entry-notes"
            :required="false"
        />
    </div>
</div>
