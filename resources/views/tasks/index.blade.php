<x-app-layout>
    <x-slot name="header">Tasks</x-slot>
    @php($showTaskStatusModal = old('modal_form') === 'task-status-modal')
    @php($showTaskLogTimeModal = old('modal_form') === 'task-log-time-modal')

    <section class="page-hero p-4 p-lg-5 mb-4">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <div class="page-kicker mb-2">Task management</div>
                <h2 class="page-title h1 mb-3">Manage work with clearer status and priority signals.</h2>
                <p class="page-subtitle mb-0">
                    Filter, review, and edit tasks from a table that stays usable on desktop and mobile.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end">
                @can('create', \App\Models\Task::class)
                    <div class="d-flex flex-column flex-lg-row justify-content-lg-end gap-2">
                        <a href="{{ route('tasks.import.create') }}" class="btn btn-outline-secondary btn-lg">
                            <i class="bi bi-upload me-1"></i> Import CSV
                        </a>
                        <a href="{{ route('tasks.create') }}" class="btn btn-primary btn-lg">
                            <i class="bi bi-plus-lg me-1"></i> Create Task
                        </a>
                    </div>
                @endcan
            </div>
        </div>
    </section>

    <div class="surface-card p-4 mb-4">
        <form class="row g-3 align-items-end" method="GET" action="{{ route('tasks.index') }}">
            <div class="col-lg-4">
                <label class="form-label">Search</label>
                <input class="form-control" name="search" placeholder="Search tasks" value="{{ request('search') }}">
            </div>
            <div class="col-lg-2">
                <label class="form-label">Status</label>
                <select class="form-select" name="status">
                    <option value="">All statuses</option>
                    @foreach (\App\Models\Task::statusOptions() as $status => $label)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-3">
                <label class="form-label">Client</label>
                <select class="form-select" name="client_id">
                    <option value="">All clients</option>
                    @foreach ($clients as $client)
                        <option value="{{ $client->id }}" @selected(request('client_id') == $client->id)>{{ $client->name }}</option>
                    @endforeach
                </select>
            </div>
            @if (auth()->user()->canManageTeam())
                <div class="col-lg-3">
                    <label class="form-label">Assigned</label>
                    <select class="form-select" name="assigned_user_id">
                        <option value="">All assignees</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected(request('assigned_user_id') == $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-12 d-flex flex-wrap gap-2">
                <button class="btn btn-primary filter-action-btn" type="submit" data-loading-text="Filtering...">
                    <i class="bi bi-search me-1"></i> Search
                </button>
                <a class="btn btn-outline-secondary filter-action-btn filter-action-icon-btn" href="{{ route('tasks.index') }}" aria-label="Reset filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>

    <div class="table-panel">
        <div class="table-panel-header">
            <div>
                <div class="table-panel-eyebrow mb-1">Backlog</div>
                <h3 class="table-panel-title mb-0">Task table</h3>
            </div>
            <span class="badge badge-soft">{{ $tasks->total() }} total</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Task</th>
                        <th>Client</th>
                        <th>Assigned</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tasks as $task)
                        <tr>
                            <td>
                                @can('view', $task)
                                    <a
                                        href="{{ route('tasks.show', $task) }}"
                                        class="btn btn-link p-0 fw-semibold text-decoration-none align-baseline"
                                    >
                                        {{ $task->title }}
                                    </a>
                                @else
                                    <span class="fw-semibold">{{ $task->title }}</span>
                                @endcan
                            </td>
                            <td>{{ $task->client->name }}</td>
                            <td>{{ $task->assignedUser?->name ?: 'Unassigned' }}</td>
                            <td>
                                @can('update', $task)
                                    <button
                                        type="button"
                                        class="btn btn-link p-0 text-decoration-none align-baseline"
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
                                        <span class="badge {{ $task->statusBadgeClass() }}">{{ $task->statusLabel() }}</span>
                                    </button>
                                @else
                                    <span class="badge {{ $task->statusBadgeClass() }}">{{ $task->statusLabel() }}</span>
                                @endcan
                            </td>
                            <td><span class="badge {{ $task->priorityBadgeClass() }}">{{ $task->priorityLabel() }}</span></td>
                            <td class="text-end">
                                @can('view', $task)
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('tasks.show', $task) }}">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                @endcan
                                @can('update', $task)
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-success"
                                        data-task-log-time-trigger
                                        data-task-id="{{ $task->id }}"
                                        data-task-title="{{ $task->title }}"
                                        data-task-client="{{ $task->client->name }}"
                                        data-task-status="{{ $task->status }}"
                                        data-task-status-label="{{ $task->statusLabel() }}"
                                        data-task-status-class="{{ $task->statusBadgeClass() }}"
                                    >
                                        <i class="bi bi-stopwatch me-1"></i> Log Time
                                    </button>
                                @endcan
                                @can('update', $task)
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('tasks.edit', $task) }}">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endcan
                                @can('delete', $task)
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-danger"
                                        data-delete-confirm
                                        data-delete-action="{{ route('tasks.destroy', $task) }}"
                                        data-delete-title="Delete Task"
                                        data-delete-message="Are you sure you want to delete {{ $task->title }}? This action cannot be undone."
                                        data-delete-submit="Delete Task"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
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

    @include('tasks._log-time-modal', [
        'selectedTask' => $logTimeTask ?? null,
        'showModal' => $showTaskLogTimeModal,
    ])

    <div class="mt-3">{{ $tasks->links('pagination::bootstrap-5') }}</div>
</x-app-layout>
