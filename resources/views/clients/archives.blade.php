<x-app-layout>
    <x-slot name="header">Client Archives</x-slot>

    <section class="page-hero p-4 p-lg-5 mb-4">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <div class="page-kicker mb-2">Archived records</div>
                <h2 class="page-title h1 mb-3">Review archived clients and remove them permanently.</h2>
                <p class="page-subtitle mb-0">
                    Keep inactive accounts separated from active work and clean them up when they are no longer needed.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end">
                @can('create', App\Models\Client::class)
                    <a href="{{ route('clients.create') }}" class="btn btn-primary btn-lg">
                        <i class="bi bi-plus-lg me-1"></i> Add Client
                    </a>
                @endcan
            </div>
        </div>
    </section>

    <div class="surface-card p-4 mb-4">
        <form class="row g-3 align-items-end" method="GET">
            <div class="col-lg-9">
                <label class="form-label">Search</label>
                <input class="form-control" name="search" placeholder="Search archived clients" value="{{ request('search') }}">
            </div>
            <div class="col-lg-3 d-flex gap-2">
                <button class="btn btn-primary flex-grow-1" type="submit" data-loading-text="Searching...">
                    <i class="bi bi-search me-1"></i> Search
                </button>
                <a class="btn btn-outline-secondary" href="{{ route('clients.archives') }}">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>

    <div class="table-panel">
        <div class="table-panel-header">
            <div>
                <div class="table-panel-eyebrow mb-1">Directory</div>
                <h3 class="table-panel-title mb-0">Archived clients</h3>
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
                                <span class="badge badge-soft">Archived</span>
                            </td>
                            <td class="text-end">
                                @can('view', $client)
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('clients.show', $client) }}">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                @endcan
                                @can('restore', $client)
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-success"
                                        data-delete-confirm
                                        data-delete-action="{{ route('clients.restore', $client) }}"
                                        data-delete-title="Restore Client"
                                        data-delete-message="Are you sure you want to restore {{ $client->name }} to active clients?"
                                        data-delete-submit="Restore Client"
                                        data-delete-method="PATCH"
                                    >
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                    </button>
                                @endcan
                                @can('forceDelete', $client)
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-danger"
                                        data-delete-confirm
                                        data-delete-action="{{ route('clients.force-delete', $client) }}"
                                        data-delete-title="Delete Archived Client"
                                        data-delete-message="Are you sure you want to permanently delete {{ $client->name }}? This action cannot be undone."
                                        data-delete-submit="Delete Permanently"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="table-empty">No archived clients found for the current filters.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $clients->links('pagination::bootstrap-5') }}</div>
</x-app-layout>
