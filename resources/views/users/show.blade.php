<x-app-layout>
    <x-slot name="header">{{ $user->name }}</x-slot>

    <section class="page-hero p-4 p-lg-5 mb-4">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <div class="page-kicker mb-2">User profile</div>
                <h2 class="page-title h1 mb-3">{{ $user->name }}</h2>
                <p class="page-subtitle mb-0">
                    Review role, team placement, and activity counts for this account.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end d-flex justify-content-lg-end gap-2 flex-wrap">
                @can('update', $user)
                    <a class="btn btn-primary" href="{{ route('users.edit', $user) }}">
                        <i class="bi bi-pencil me-1"></i> Edit User
                    </a>
                @endcan
                <a class="btn btn-outline-secondary" href="{{ route('users.index') }}">
                    Back
                </a>
            </div>
        </div>
    </section>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="surface-card p-4 h-100">
                <div class="section-kicker mb-2">Identity</div>
                <h3 class="h5 mb-3">{{ $user->name }}</h3>
                <div class="mb-2">
                    <div class="small text-muted">Email</div>
                    <div class="fw-semibold">{{ $user->email }}</div>
                </div>
                <div class="mb-2">
                    <div class="small text-muted">Role</div>
                    <div class="fw-semibold text-capitalize">{{ $user->role }}</div>
                </div>
                <div>
                    <div class="small text-muted">Team</div>
                    <div class="fw-semibold">{{ $user->team?->name ?: 'No team' }}</div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="surface-card p-4 h-100">
                        <div class="section-kicker mb-1">Workload</div>
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="display-6 fw-semibold mb-0">{{ $user->assigned_tasks_count }}</div>
                                <div class="muted-copy">Assigned tasks</div>
                            </div>
                            <i class="bi bi-list-check stat-icon"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="surface-card p-4 h-100">
                        <div class="section-kicker mb-1">Activity</div>
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="display-6 fw-semibold mb-0">{{ $user->time_entries_count }}</div>
                                <div class="muted-copy">Time entries logged</div>
                            </div>
                            <i class="bi bi-clock-history stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>

            @can('delete', $user)
                @if (auth()->id() !== $user->id)
                    <div class="surface-card p-4 mt-4">
                        <div class="section-kicker mb-1">Danger zone</div>
                        <h3 class="h5 mb-2">Remove this user</h3>
                        <p class="muted-copy mb-3">This will delete the account and clear user assignments from related records where the database allows it.</p>
                        <button
                            type="button"
                            class="btn btn-danger"
                            data-delete-confirm
                            data-delete-action="{{ route('users.destroy', $user) }}"
                            data-delete-title="Delete User"
                            data-delete-message="Are you sure you want to delete {{ $user->name }}? This action cannot be undone."
                            data-delete-submit="Delete User"
                        >
                            Delete User
                        </button>
                    </div>
                @endif
            @endcan
        </div>
    </div>
</x-app-layout>
