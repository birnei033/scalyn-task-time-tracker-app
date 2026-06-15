<x-app-layout>
    <x-slot name="header">Clients</x-slot>

    <section class="page-hero p-4 p-lg-5 mb-4">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <div class="page-kicker mb-2">Client records</div>
                <h2 class="page-title h1 mb-3">Keep client data organized and accessible.</h2>
                <p class="page-subtitle mb-0">
                    Search, filter, and manage active clients with a cleaner table layout and branded controls.
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

    @include('clients._tabs', ['activeTab' => 'clients'])

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
                                <span class="badge badge-brand">Active</span>
                            </td>
                            <td class="text-end">
                                @can('update', $client)
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('clients.edit', $client) }}">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endcan
                                @can('delete', $client)
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-danger"
                                        data-delete-confirm
                                        data-delete-action="{{ route('clients.destroy', $client) }}"
                                        data-delete-title="Archive Client"
                                        data-delete-message="Are you sure you want to archive {{ $client->name }}?"
                                        data-delete-submit="Archive Client"
                                    >
                                        <i class="bi bi-archive"></i>
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
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
