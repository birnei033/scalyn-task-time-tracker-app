<x-app-layout>
    <x-slot name="header">Import Results</x-slot>

    <section class="page-hero p-4 p-lg-5 mb-4">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <div class="page-kicker mb-2">Import complete</div>
                <h2 class="page-title h1 mb-3">We processed your CSV file.</h2>
                <p class="page-subtitle mb-0">
                    Review the created tasks and any rows that were skipped or rejected.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <a href="{{ route('tasks.import.create') }}" class="btn btn-outline-secondary btn-lg">
                    Import Another File
                </a>
            </div>
        </div>
    </section>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="surface-card p-4 h-100">
                <div class="section-kicker mb-2">Created</div>
                <div class="display-6 mb-0">{{ $createdCount }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="surface-card p-4 h-100">
                <div class="section-kicker mb-2">Failed</div>
                <div class="display-6 mb-0">{{ $failedCount }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="surface-card p-4 h-100">
                <div class="section-kicker mb-2">Skipped Blank Rows</div>
                <div class="display-6 mb-0">{{ $skippedCount }}</div>
            </div>
        </div>
    </div>

    <div class="surface-card p-4 p-lg-5 mb-4">
        <div class="section-kicker mb-2">Created tasks</div>
        @forelse ($createdTasks as $task)
            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                <div>
                    <div class="fw-semibold">{{ $task->title }}</div>
                    <div class="text-muted small">{{ $task->client?->name }}</div>
                </div>
                <a href="{{ route('tasks.show', $task) }}" class="btn btn-outline-primary btn-sm">View</a>
            </div>
        @empty
            <div class="table-empty text-start">No tasks were created.</div>
        @endforelse
    </div>

    <div class="surface-card p-4 p-lg-5">
        <div class="section-kicker mb-2">Row results</div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Row</th>
                        <th>Status</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($results as $result)
                        <tr>
                            <td>{{ $result['row'] }}</td>
                            <td>
                                <span class="badge {{ $result['status'] === 'created' ? 'bg-success' : 'bg-danger' }}">
                                    {{ ucfirst($result['status']) }}
                                </span>
                            </td>
                            <td>
                                @foreach ($result['messages'] as $message)
                                    <div>{{ $message }}</div>
                                @endforeach
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
