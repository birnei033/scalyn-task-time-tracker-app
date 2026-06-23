<x-app-layout>
    <x-slot name="header">Timesheets</x-slot>
    @php
        $baseQuery = request()->except(['sort', 'direction']);
        $editingEntryId = old('editing_entry', request('editing_entry'));
        $showEditModal = old('modal_form') === 'time-entry-edit-modal' || request()->filled('editing_entry');
        $selectedEntry = $editingEntryId
            ? ($entries->firstWhere('id', (int) $editingEntryId) ?: \App\Models\TimeEntry::with(['user', 'task.client'])->find($editingEntryId))
            : null;
        $sortUrl = fn (string $column) => route('timesheets.index', array_merge($baseQuery, [
            'sort' => $column,
            'direction' => $sort === $column && $direction === 'asc' ? 'desc' : 'asc',
        ]));
        $sortIcon = fn (string $column) => $sort === $column
            ? ($direction === 'asc' ? 'bi-caret-up-fill' : 'bi-caret-down-fill')
            : 'bi-arrow-down-up';
    @endphp

    <section class="page-hero p-4 p-lg-5 mb-4">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <div class="page-kicker mb-2">Timesheet review</div>
                <h2 class="page-title h1 mb-3">Review totals with a clearer daily, weekly, or monthly view.</h2>
                <p class="page-subtitle mb-0">
                    Use the filters to narrow the dataset and compare entries without losing readability.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <div class="hero-metric ms-lg-auto">
                    <div class="label mb-1">Total Time</div>
                    <div class="value">{{ \App\Support\TimeDisplay::formatHours($totalHours) }}</div>
                </div>
            </div>
        </div>
    </section>

    <form class="surface-card p-4 mb-4" method="GET" data-timesheet-filter-form>
        <input type="hidden" name="sort" value="{{ $sort }}">
        <input type="hidden" name="direction" value="{{ $direction }}">
        <div class="row g-3 align-items-end">
            <div class="col-lg-2">
                <label class="form-label">View</label>
                <select class="form-select" name="view" data-timesheet-view-select>
                    <option value="daily" @selected($view === 'daily')>Daily</option>
                    <option value="weekly" @selected($view === 'weekly')>Weekly</option>
                    <option value="monthly" @selected($view === 'monthly')>Monthly</option>
                </select>
            </div>
            <div class="col-lg-2">
                <label class="form-label">From</label>
                <input type="date" class="form-control" name="from" value="{{ $from }}" data-timesheet-from-input>
            </div>
            <div class="col-lg-2">
                <label class="form-label">To</label>
                <input type="date" class="form-control" name="to" value="{{ $to }}" data-timesheet-to-input>
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
                    <label class="form-label">User</label>
                    <select class="form-select" name="user_id">
                        <option value="">All users</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-12 d-flex flex-wrap gap-2">
                <button class="btn btn-primary" data-loading-text="Applying...">
                    <i class="bi bi-funnel me-1"></i> Apply filters
                </button>
                <a class="btn btn-outline-secondary filter-action-btn filter-action-icon-btn" href="{{ route('timesheets.index') }}" aria-label="Reset filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </div>
    </form>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="metric-card">
                <div class="metric-label">Total Time</div>
                <div class="metric-value mt-1">{{ \App\Support\TimeDisplay::formatHours($totalHours) }}</div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="surface-card p-4">
                <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                    <div class="metric-label mb-0">Daily Totals</div>
                </div>
                <div>
                    @foreach ($dailyTotals as $date => $hours)
                        <span class="badge badge-soft me-2 mb-2">{{ $date }}: {{ \App\Support\TimeDisplay::formatHours($hours) }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="table-panel">
        <div class="table-panel-header">
            <div>
                <div class="table-panel-eyebrow mb-1">Timesheet entries</div>
                <h3 class="table-panel-title mb-0">Sortable log table</h3>
            </div>
            <span class="badge badge-soft">{{ $entries->count() }} entries</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th scope="col" aria-sort="{{ $sort === 'date' ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                            <a class="table-sort-link" href="{{ $sortUrl('date') }}">Date <i class="bi {{ $sortIcon('date') }} table-sort-icon"></i></a>
                        </th>
                        <th scope="col" aria-sort="{{ $sort === 'user' ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                            <a class="table-sort-link" href="{{ $sortUrl('user') }}">User <i class="bi {{ $sortIcon('user') }} table-sort-icon"></i></a>
                        </th>
                        <th scope="col" aria-sort="{{ $sort === 'client' ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                            <a class="table-sort-link" href="{{ $sortUrl('client') }}">Client <i class="bi {{ $sortIcon('client') }} table-sort-icon"></i></a>
                        </th>
                        <th scope="col" aria-sort="{{ $sort === 'task' ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                            <a class="table-sort-link" href="{{ $sortUrl('task') }}">Task <i class="bi {{ $sortIcon('task') }} table-sort-icon"></i></a>
                        </th>
                        <th scope="col" aria-sort="{{ $sort === 'notes' ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                            <a class="table-sort-link" href="{{ $sortUrl('notes') }}">Notes <i class="bi {{ $sortIcon('notes') }} table-sort-icon"></i></a>
                        </th>
                        <th scope="col" class="text-end" aria-sort="{{ $sort === 'hours' ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                            <a class="table-sort-link justify-content-end w-100" href="{{ $sortUrl('hours') }}">Time <i class="bi {{ $sortIcon('hours') }} table-sort-icon"></i></a>
                        </th>
                        <th scope="col" class="text-end">
                            <span class="visually-hidden">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($entries as $entry)
                        <tr>
                            <td>{{ $entry->date->format('M d, Y') }}</td>
                            <td>
                                <x-user-identity :name="$entry->user->name" seed="{{ $entry->user_id }}" />
                            </td>
                            <td>{{ $entry->task->client->name }}</td>
                            <td>{{ $entry->task->title }}</td>
                            <td>{{ \App\Support\RichText::excerpt($entry->notes) }}</td>
                            <td class="text-end fw-semibold">{{ \App\Support\TimeDisplay::formatHours($entry->hours) }}</td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    @can('update', $entry)
                                        <a
                                            class="btn btn-outline-secondary table-action-icon-btn table-action-view"
                                            href="{{ route('timesheets.index', array_merge(request()->query(), ['editing_entry' => $entry->id])) }}"
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
                            <td colspan="7">
                                <div class="table-empty">No entries for this timesheet.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="table-panel-footer">
            <a class="table-panel-footer-action" href="{{ route('timesheets.export', request()->query()) }}" aria-label="Export filtered timesheets CSV" title="Export filtered timesheets CSV">
                <span>Export</span>
                <i class="bi bi-download"></i>
            </a>
        </div>
    </div>

    @include('time-entries._edit-modal', [
        'selectedEntry' => $selectedEntry,
        'showModal' => $showEditModal,
        'tasks' => $tasks,
        'users' => $users,
        'returnTo' => route('timesheets.index', request()->except('editing_entry')),
    ])
</x-app-layout>
