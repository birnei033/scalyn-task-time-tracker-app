<x-app-layout>
    <x-slot name="header">Tasks</x-slot>
    <x-slot name="actions">
        @can('create', \App\Models\Task::class)
            <a href="{{ route('tasks.import.create') }}" class="btn btn-outline-secondary btn-lg">
                <i class="bi bi-upload me-1"></i> Import CSV
            </a>
            <a href="{{ route('tasks.create') }}" class="btn btn-primary btn-lg">
                <i class="bi bi-plus-lg me-1"></i> Create Task
            </a>
        @endcan
    </x-slot>
    @php
        $bulkSelectionEnabled = $tasks->getCollection()->contains(fn ($task) => auth()->user()->can('update', $task));
        $showTaskStatusModal = old('modal_form') === 'task-status-modal';
        $showTaskPriorityModal = old('modal_form') === 'task-priority-modal';
        $showTaskLogTimeModal = old('modal_form') === 'task-log-time-modal';
        $bulkSelectedTaskIds = old('task_ids', []);
        $bulkStatusValue = old('status');
        $statusPillClasses = [
            'open' => 'task-pill task-pill-open',
            'in_progress' => 'task-pill task-pill-in-progress',
            'completed' => 'task-pill task-pill-completed',
            'on_hold' => 'task-pill task-pill-on-hold',
            'to_review' => 'task-pill task-pill-review',
        ];
        $priorityPillClasses = [
            'low' => 'task-pill task-pill-low',
            'medium' => 'task-pill task-pill-medium',
            'high' => 'task-pill task-pill-high',
        ];
    @endphp

    <div class="surface-card task-filter-card p-4 mb-4">
        <form class="row g-3 align-items-end task-filter-form" method="GET" action="{{ route('tasks.index') }}">
            <div class="col-12 col-md-6 col-xl-3 task-filter-search">
                <label class="form-label">Search</label>
                <div class="task-search-wrap">
                    <i class="bi bi-search task-search-icon" aria-hidden="true"></i>
                    <input class="form-control task-search-input" name="search" placeholder="Search tasks" value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-2">
                <label class="form-label">Status</label>
                <select class="form-select" name="status">
                    <option value="">All statuses</option>
                    @foreach (\App\Models\Task::statusOptions() as $status => $label)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-6 col-xl-2">
                <label class="form-label">Client</label>
                <select class="form-select" name="client_id">
                    <option value="">All clients</option>
                    @foreach ($clients as $client)
                        <option value="{{ $client->id }}" @selected(request('client_id') == $client->id)>{{ $client->name }}</option>
                    @endforeach
                </select>
            </div>
            @if (auth()->user()->canManageTeam())
                <div class="col-12 col-md-6 col-xl-2">
                    <label class="form-label">Assigned</label>
                    <select class="form-select" name="assigned_user_id">
                        <option value="">All assignees</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected(request('assigned_user_id') == $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-12 col-xl-auto task-filter-actions">
                <button class="btn btn-primary filter-action-btn" type="submit" data-loading-text="Filtering...">
                    <i class="bi bi-search me-1"></i> Search
                </button>
                <a class="btn btn-outline-secondary filter-action-btn filter-action-icon-btn" href="{{ route('tasks.index') }}" aria-label="Reset filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>

    <div class="table-panel task-table-panel">
        <div class="table-panel-header">
            <div>
                <div class="table-panel-eyebrow mb-1">Backlog</div>
                <h3 class="table-panel-title mb-0">Task table</h3>
            </div>
            <span class="badge badge-soft">{{ $tasks->total() }} total</span>
        </div>

        @if ($bulkSelectionEnabled)
            @php
                $bulkTaskIdsError = $errors->first('task_ids') ?: $errors->first('task_ids.0');
            @endphp
            <div class="task-bulk-panel" data-task-bulk-status-panel>
                <form
                    method="POST"
                    action="{{ route('tasks.bulk-status.update') }}"
                    class="task-bulk-form d-flex flex-column flex-lg-row align-items-start align-items-lg-center gap-3 gap-lg-4"
                    data-task-bulk-status-form
                    id="task-bulk-status-form"
                >
                    @csrf
                    <input type="hidden" name="return_to" value="{{ request()->fullUrl() }}">

                    <div class="task-bulk-fields d-flex flex-column flex-sm-row align-items-start align-items-sm-center flex-wrap gap-3 gap-sm-4 flex-grow-1">
                        <div class="task-bulk-control d-flex flex-column gap-1">
                            <label class="form-label mb-0 small text-uppercase fw-semibold text-muted">Bulk status</label>
                            <select class="form-select form-select-sm @error('status') is-invalid @enderror" name="status" data-task-bulk-status-select>
                                <option value="">Choose a status</option>
                                @foreach (\App\Models\Task::statusOptions() as $status => $label)
                                    <option value="{{ $status }}" @selected($bulkStatusValue === $status)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <div class="task-bulk-selected d-flex flex-column gap-1">
                            <div class="small text-uppercase fw-semibold text-muted">Selected tasks</div>
                            <div class="fw-semibold" data-task-bulk-status-count>0 selected</div>
                        </div>
                    </div>

                    <div class="task-bulk-actions d-flex align-items-center ms-lg-auto">
                        <button
                            class="btn btn-outline-secondary btn-sm px-3 task-bulk-apply"
                            type="submit"
                            data-task-bulk-status-apply
                            data-loading-text="Applying status..."
                            disabled
                        >
                            <i class="bi bi-check2-circle me-1"></i> Apply Status
                        </button>

                        @if ($bulkTaskIdsError)
                            <div class="invalid-feedback d-block m-0">{{ $bulkTaskIdsError }}</div>
                        @endif
                    </div>
                </form>
            </div>
        @endif

        <div class="table-responsive">
            <table class="table align-middle task-table">
                <thead>
                    <tr>
                        @if ($bulkSelectionEnabled)
                            <th class="text-center task-table-check-column" style="width: 1%">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    aria-label="Select all tasks"
                                    data-task-bulk-status-select-all
                                >
                            </th>
                        @endif
                        <th class="task-table-task-column">Task</th>
                        <th class="task-table-client-column">Client</th>
                        <th class="task-table-assigned-column">Assigned</th>
                        <th class="task-table-status-column">Status</th>
                        <th class="task-table-priority-column">Priority</th>
                        <th class="text-end task-table-actions-column">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tasks as $task)
                        @php
                            $assignedName = $task->assignedUser?->name ?: 'Unassigned';
                            $assignedParts = preg_split('/\s+/', trim($assignedName)) ?: [];
                            $assignedInitials = collect($assignedParts)
                                ->filter()
                                ->map(fn ($part) => mb_substr($part, 0, 1))
                                ->take(2)
                                ->implode('');
                            $assignedInitials = $assignedInitials ?: 'U';
                            $assigneeAvatarClasses = [
                                'task-assignee-avatar-1',
                                'task-assignee-avatar-2',
                                'task-assignee-avatar-3',
                                'task-assignee-avatar-4',
                                'task-assignee-avatar-5',
                            ];
                            $assignedAvatarClass = $assigneeAvatarClasses[$task->id % count($assigneeAvatarClasses)];
                        @endphp
                        <tr>
                            @if ($bulkSelectionEnabled)
                                <td class="text-center">
                                    @can('update', $task)
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            name="task_ids[]"
                                            value="{{ $task->id }}"
                                            aria-label="Select {{ $task->title }}"
                                            data-task-bulk-status-checkbox
                                            form="task-bulk-status-form"
                                            @checked(in_array((string) $task->id, $bulkSelectedTaskIds, true))
                                        >
                                    @else
                                        <span class="text-muted">&mdash;</span>
                                    @endcan
                                </td>
                            @endif
                            <td class="task-table-task-cell">
                                @can('view', $task)
                                    <a
                                        href="{{ route('tasks.show', $task) }}"
                                        class="task-table-link"
                                    >
                                        {{ $task->title }}
                                    </a>
                                @else
                                    <span class="task-table-link">{{ $task->title }}</span>
                                @endcan
                            </td>
                            <td class="task-table-client-cell">{{ $task->client->name }}</td>
                            <td class="task-table-assigned-cell">
                                <span class="task-assignee">
                                    <span class="task-assignee-avatar {{ $assignedAvatarClass }}">{{ $assignedInitials }}</span>
                                    <span class="task-assignee-name">{{ $assignedName }}</span>
                                </span>
                            </td>
                            <td class="task-table-status-cell">
                                @can('update', $task)
                                    <button
                                        type="button"
                                        class="task-pill-trigger"
                                        aria-label="Update status for {{ $task->title }}"
                                        data-task-status-trigger
                                        data-task-id="{{ $task->id }}"
                                        data-task-title="{{ $task->title }}"
                                        data-task-client="{{ $task->client->name }}"
                                        data-task-status="{{ $task->status }}"
                                        data-task-status-label="{{ $task->statusLabel() }}"
                                        data-task-status-class="{{ $task->statusBadgeClass() }}"
                                        data-task-status-action="{{ route('tasks.status.update', $task) }}"
                                    >
                                        <span class="{{ $statusPillClasses[$task->status] ?? 'task-pill' }}">
                                            <span class="task-pill-dot"></span>
                                            <span>{{ $task->statusLabel() }}</span>
                                        </span>
                                    </button>
                                @else
                                    <span class="{{ $statusPillClasses[$task->status] ?? 'task-pill' }}">
                                        <span class="task-pill-dot"></span>
                                        <span>{{ $task->statusLabel() }}</span>
                                    </span>
                                @endcan
                            </td>
                            <td class="task-table-priority-cell">
                                @can('update', $task)
                                    <button
                                        type="button"
                                        class="task-pill-trigger"
                                        aria-label="Update priority for {{ $task->title }}"
                                        data-task-priority-trigger
                                        data-task-id="{{ $task->id }}"
                                        data-task-title="{{ $task->title }}"
                                        data-task-client="{{ $task->client->name }}"
                                        data-task-priority="{{ $task->priority }}"
                                        data-task-priority-label="{{ $task->priorityLabel() }}"
                                        data-task-priority-class="{{ $task->priorityBadgeClass() }}"
                                        data-task-priority-action="{{ route('tasks.priority.update', $task) }}"
                                        data-task-return-to="{{ request()->fullUrl() }}"
                                    >
                                        <span class="{{ $priorityPillClasses[$task->priority] ?? 'task-pill' }}">
                                            <span class="task-pill-dot"></span>
                                            <span>{{ $task->priorityLabel() }}</span>
                                        </span>
                                    </button>
                                @else
                                    <span class="{{ $priorityPillClasses[$task->priority] ?? 'task-pill' }}">
                                        <span class="task-pill-dot"></span>
                                        <span>{{ $task->priorityLabel() }}</span>
                                    </span>
                                @endcan
                            </td>
                            <td class="text-end task-table-actions-cell">
                                <div class="task-actions">
                                    @can('view', $task)
                                        <a class="btn btn-outline-secondary task-action-icon-btn task-action-view" href="{{ route('tasks.show', $task) }}" aria-label="View {{ $task->title }}">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    @endcan
                                    @can('update', $task)
                                        <button
                                            type="button"
                                            class="btn btn-outline-success task-action-btn"
                                            data-task-log-time-trigger
                                            data-task-id="{{ $task->id }}"
                                            data-task-title="{{ $task->title }}"
                                            data-task-client="{{ $task->client->name }}"
                                            data-task-status="{{ $task->status }}"
                                            data-task-status-label="{{ $task->statusLabel() }}"
                                            data-task-status-class="{{ $task->statusBadgeClass() }}"
                                            aria-label="Log time for {{ $task->title }}"
                                        >
                                            <i class="bi bi-stopwatch"></i>
                                        </button>
                                    @endcan
                                    @can('update', $task)
                                        <a class="btn btn-outline-secondary task-action-icon-btn task-action-edit" href="{{ route('tasks.edit', $task) }}" aria-label="Edit {{ $task->title }}">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    @endcan
                                    @can('delete', $task)
                                        <button
                                            type="button"
                                            class="btn btn-outline-danger task-action-icon-btn task-action-delete"
                                            data-delete-confirm
                                            data-delete-action="{{ route('tasks.destroy', $task) }}"
                                            data-delete-title="Delete Task"
                                            data-delete-message="Are you sure you want to delete {{ $task->title }}? This action cannot be undone."
                                            data-delete-submit="Delete Task"
                                            aria-label="Delete {{ $task->title }}"
                                        >
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 6 + ($bulkSelectionEnabled ? 1 : 0) }}">
                                <div class="table-empty">No tasks found for the current filters.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('tasks._status-modal', [
        'selectedTask' => $statusTask ?? null,
        'showModal' => $showTaskStatusModal,
    ])

    @include('tasks._priority-modal', [
        'selectedTask' => $priorityTask ?? null,
        'showModal' => $showTaskPriorityModal,
    ])

    @include('tasks._log-time-modal', [
        'selectedTask' => $logTimeTask ?? null,
        'showModal' => $showTaskLogTimeModal,
    ])

    <div class="mt-3">{{ $tasks->links('pagination::bootstrap-5') }}</div>
</x-app-layout>
