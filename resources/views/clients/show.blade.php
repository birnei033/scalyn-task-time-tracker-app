<x-app-layout>
    <x-slot name="header">{{ $client->name }}</x-slot>
    <x-slot name="actions">
        @can('update', $client)
            <a class="btn btn-primary" href="{{ route('clients.edit', $client) }}">
                <i class="bi bi-pencil me-1"></i> Edit Client
            </a>
        @endcan
        <a class="btn btn-outline-secondary" href="{{ $client->status === 'archived' ? route('clients.archives') : route('clients.index') }}">
            Back
        </a>
    </x-slot>

    <div class="row g-4">
        <div class="col-lg-4 d-grid gap-4">
            <div class="surface-card p-4">
                <div class="section-kicker mb-2">Details</div>
                <h3 class="h5 mb-3">Client details</h3>
                <div class="d-grid gap-2">
                    <div><strong>Company:</strong> {{ $client->company ?: 'N/A' }}</div>
                    <div><strong>Contact:</strong> {{ $client->contact_person ?: 'N/A' }}</div>
                    <div><strong>Email:</strong> {{ $client->email ?: 'N/A' }}</div>
                </div>
            </div>

            <div class="surface-card p-4">
                <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
                    <div>
                        <div class="section-kicker mb-1">Usage</div>
                        <h3 class="h5 mb-0">This month</h3>
                    </div>
                    <i class="bi bi-pie-chart stat-icon"></i>
                </div>

                <div class="d-grid gap-3">
                    <div>
                        <div class="stat-label mb-1">Total Hours this month</div>
                        <div class="stat-value">{{ \App\Support\TimeDisplay::formatHours($monthlyHours) }}</div>
                    </div>

                    @if ($client->budget_per_month !== null)
                        @if ($monthlyExcessMinutes !== null && $monthlyExcessMinutes > 0)
                            <div>
                                <div class="stat-label mb-1">Excess hours of the budget per month</div>
                                <div class="stat-value text-danger">{{ \App\Support\TimeDisplay::formatMinutes($monthlyExcessMinutes) }}</div>
                            </div>
                        @else
                            <div class="muted-copy mb-0">Within the monthly budget.</div>
                        @endif
                    @else
                        <div class="muted-copy mb-0">No monthly budget set.</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="table-panel">
                <div class="d-flex align-items-center justify-content-between gap-3 border-bottom px-4 py-3">
                    <div>
                        <div class="section-kicker mb-1">Work</div>
                        <h3 class="h5 mb-0">Tasks</h3>
                    </div>
                    <span class="badge badge-soft">{{ $client->tasks->count() }} tasks</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Task</th>
                                <th>Assigned</th>
                                <th>Status</th>
                                <th class="text-end">Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($client->tasks as $task)
                                <tr>
                                    <td>{{ $task->title }}</td>
                                    <td>
                                        <x-user-identity :name="$task->assignedUser?->name" placeholder="Unassigned" :seed="$task->assigned_user_id ?? $task->id" />
                                    </td>
                                    <td><span class="badge {{ $task->statusBadgeClass() }}">{{ $task->statusLabel() }}</span></td>
                                    <td class="text-end fw-semibold">{{ \App\Support\TimeDisplay::formatHours($task->timeEntries->sum('hours')) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">
                                        <div class="table-empty">No tasks have been linked to this client yet.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
