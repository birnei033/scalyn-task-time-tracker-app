<x-app-layout>
    <x-slot name="header">Time Entry</x-slot>
    @php
        $returnTo = route('time-entries.index', request()->except('editing_entry'));
    @endphp
    <x-slot name="actions">
        <a href="{{ route('time-entries.create') }}" class="btn btn-primary btn-lg">
            <i class="bi bi-plus-lg me-1"></i> Add Time
        </a>
    </x-slot>

    <div class="surface-card p-4 mb-4">
        <form class="row g-3 align-items-end" method="GET">
            <div class="col-lg-4">
                <label class="form-label">From</label>
                <input type="date" class="form-control" name="from" value="{{ request('from') }}">
            </div>
            <div class="col-lg-4">
                <label class="form-label">To</label>
                <input type="date" class="form-control" name="to" value="{{ request('to') }}">
            </div>
            <div class="col-lg-4 d-flex gap-2">
                <button class="btn btn-primary flex-grow-1" data-loading-text="Filtering...">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                <a class="btn btn-outline-secondary" href="{{ route('time-entries.index') }}">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>

    <div class="table-panel">
        <div class="table-panel-header">
            <div>
                <div class="table-panel-eyebrow mb-1">Entries</div>
                <h3 class="table-panel-title mb-0">Time entry table</h3>
            </div>
            <span class="badge badge-soft">{{ $entries->total() }} total</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>User</th>
                        <th>Client / Task</th>
                        <th>Notes</th>
                        <th class="text-end">Time</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($entries as $entry)
                        <tr>
                            <td>{{ $entry->date->format('M d, Y') }}</td>
                            <td>
                                <x-user-identity :name="$entry->user->name" seed="{{ $entry->user_id }}" />
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $entry->task->client->name }}</div>
                                <div class="small text-muted">{{ $entry->task->title }}</div>
                            </td>
                            <td>{{ \App\Support\RichText::excerpt($entry->notes) }}</td>
                            <td class="text-end fw-semibold">{{ \App\Support\TimeDisplay::formatHours($entry->hours) }}</td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    @can('update', $entry)
                                        <a
                                            class="btn btn-outline-secondary table-action-icon-btn table-action-view"
                                            href="{{ route('time-entries.edit', $entry) }}"
                                            aria-label="Edit time entry for {{ $entry->task->title }}"
                                            data-time-entry-edit-trigger
                                            data-time-entry-edit-action="{{ route('time-entries.update', $entry) }}"
                                            data-time-entry-edit-id="{{ $entry->id }}"
                                            data-time-entry-edit-user-id="{{ $entry->user_id }}"
                                            data-time-entry-edit-task-id="{{ $entry->task_id }}"
                                            data-time-entry-edit-date="{{ $entry->date->toDateString() }}"
                                            data-time-entry-edit-minutes="{{ \App\Support\TimeDisplay::hoursToMinutes($entry->hours) }}"
                                            data-time-entry-edit-notes="{{ e($entry->notes ?? '') }}"
                                        >
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    @endcan
                                    @can('delete', $entry)
                                        <button
                                            type="button"
                                            class="btn btn-outline-danger table-action-icon-btn table-action-delete"
                                            data-delete-confirm
                                            data-delete-action="{{ route('time-entries.destroy', $entry) }}"
                                            data-delete-title="Delete Logged Time"
                                            data-delete-message="Delete this logged time entry for {{ $entry->task->title }}? This action cannot be undone."
                                            data-delete-submit="Delete Time Entry"
                                            aria-label="Delete time entry for {{ $entry->task->title }}"
                                        >
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="table-empty">No time entries found for the current range.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $entries->links('pagination::bootstrap-5') }}</div>

    @include('time-entries._edit-modal', [
        'selectedEntry' => $selectedEntry ?? null,
        'showModal' => $showEditModal ?? false,
        'tasks' => $tasks,
        'users' => $users,
        'returnTo' => $returnTo,
    ])
</x-app-layout>
