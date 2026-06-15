<x-app-layout>
    <x-slot name="header">Time Entry</x-slot>

    <section class="page-hero p-4 p-lg-5 mb-4">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <div class="page-kicker mb-2">Time tracking</div>
                <h2 class="page-title h1 mb-3">Log work with less friction.</h2>
                <p class="page-subtitle mb-0">
                    Filter by date, create entries on a dedicated page, and review time using a table that stays readable on smaller screens.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <a href="{{ route('time-entries.create') }}" class="btn btn-primary btn-lg">
                    <i class="bi bi-plus-lg me-1"></i> Add Time
                </a>
            </div>
        </div>
    </section>

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
                        <th class="text-end">Hours</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($entries as $entry)
                        <tr>
                            <td>{{ $entry->date->format('M d, Y') }}</td>
                            <td>{{ $entry->user->name }}</td>
                            <td>
                                <div class="fw-semibold">{{ $entry->task->client->name }}</div>
                                <div class="small text-muted">{{ $entry->task->title }}</div>
                            </td>
                            <td>{{ \App\Support\RichText::excerpt($entry->notes) }}</td>
                            <td class="text-end fw-semibold">{{ number_format((float) $entry->hours, 2) }}</td>
                            <td class="text-end">
                                @can('update', $entry)
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('time-entries.edit', $entry) }}">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endcan
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
</x-app-layout>
