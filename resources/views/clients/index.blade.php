<x-app-layout>
    <x-slot name="header">Clients</x-slot>
    <x-slot name="actions">
        @can('create', App\Models\Client::class)
            <a href="{{ route('clients.create') }}" class="btn btn-primary btn-lg">
                <i class="bi bi-plus-lg me-1"></i> Add Client
            </a>
        @endcan
    </x-slot>

    <div class="surface-card p-4 mb-4">
        <form class="row g-3 align-items-end" method="GET">
            <div class="col-lg-9">
                <label class="form-label">Search</label>
                <input class="form-control" name="search" placeholder="Search active clients" value="{{ request('search') }}">
            </div>
            <div class="col-lg-3 d-flex gap-2">
                <button class="btn btn-primary flex-grow-1" type="submit" data-loading-text="Searching...">
                    <i class="bi bi-search me-1"></i> Search
                </button>
                <a class="btn btn-outline-secondary" href="{{ route('clients.index') }}">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>

    <div class="table-panel">
        <div class="table-panel-header">
            <div>
                <div class="table-panel-eyebrow mb-1">Directory</div>
                <h3 class="table-panel-title mb-0">Active clients</h3>
            </div>
            <span class="badge badge-soft">{{ $clients->total() }} total</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Company</th>
                        <th>Contact</th>
                        <th>Monthly Budget</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($clients as $client)
                        <tr>
                            <td>
                                <a href="{{ route('clients.show', $client) }}" class="fw-semibold text-decoration-none">{{ $client->name }}</a>
                            </td>
                            <td>{{ $client->company ?: '-' }}</td>
                            <td>
                                <div class="fw-semibold">{{ $client->contact_person ?: '-' }}</div>
                                <div class="small text-muted">{{ $client->email ?: 'No email' }}</div>
                            </td>
                            <td>
                                @if ($client->budget_per_month !== null)
                                    @php
                                        $budgetMinutes = (int) $client->budget_per_month;
                                        $usedMinutes = \App\Support\TimeDisplay::hoursToMinutes($client->monthly_hours ?? 0);
                                        $remainingMinutes = $budgetMinutes - $usedMinutes;
                                    @endphp
                                    <div class="fw-semibold">
                                        {{ \App\Support\TimeDisplay::formatMinutes($budgetMinutes) }}
                                        <span class="text-muted">/</span>
                                        <span class="{{ $remainingMinutes < 0 ? 'text-danger' : 'text-muted' }}">
                                            {{ \App\Support\TimeDisplay::formatMinutes(abs($remainingMinutes)) }}
                                            {{ $remainingMinutes < 0 ? 'exceeding' : 'remaining' }}
                                        </span>
                                    </div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-brand">Active</span>
                            </td>
                            <td class="text-end">
                                @can('update', $client)
                                    <a class="btn btn-outline-secondary table-action-icon-btn table-action-view" href="{{ route('clients.edit', $client) }}" aria-label="Edit {{ $client->name }}">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endcan
                                @can('delete', $client)
                                    <button
                                        type="button"
                                        class="btn btn-outline-danger table-action-icon-btn table-action-delete"
                                        data-delete-confirm
                                        data-delete-action="{{ route('clients.destroy', $client) }}"
                                        data-delete-title="Archive Client"
                                        data-delete-message="Are you sure you want to archive {{ $client->name }}?"
                                        data-delete-submit="Archive Client"
                                        aria-label="Archive {{ $client->name }}"
                                    >
                                        <i class="bi bi-archive"></i>
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="table-empty">No active clients found for the current filters.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $clients->links('pagination::bootstrap-5') }}</div>
</x-app-layout>
