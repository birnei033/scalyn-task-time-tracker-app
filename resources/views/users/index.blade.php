<x-app-layout>
    <x-slot name="header">Users</x-slot>

    <section class="page-hero p-4 p-lg-5 mb-4">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <div class="page-kicker mb-2">User directory</div>
                <h2 class="page-title h1 mb-3">Manage users, roles, and team placement in one place.</h2>
                <p class="page-subtitle mb-0">
                    Search by name or email, filter by role or team, and keep changes organized with paginated results.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end">
                @can('create', App\Models\User::class)
                    <a href="{{ route('users.create') }}" class="btn btn-primary btn-lg">
                        <i class="bi bi-person-plus me-1"></i> Add User
                    </a>
                @endcan
            </div>
        </div>
    </section>

    <div class="surface-card p-4 mb-4">
        <form class="row g-3 align-items-end" method="GET">
            <div class="col-lg-5">
                <label class="form-label">Search</label>
                <input class="form-control" name="search" placeholder="Search users" value="{{ request('search') }}">
            </div>
            <div class="col-lg-2">
                <label class="form-label">Role</label>
                <select class="form-select" name="role">
                    <option value="">All roles</option>
                    @foreach ($roles as $value => $label)
                        <option value="{{ $value }}" @selected(request('role') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-3">
                <label class="form-label">Team</label>
                <select class="form-select" name="team_id">
                    <option value="">All teams</option>
                    @foreach ($teams as $team)
                        <option value="{{ $team->id }}" @selected((string) request('team_id') === (string) $team->id)>{{ $team->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2 d-flex gap-2">
                <button class="btn btn-primary flex-grow-1" type="submit" data-loading-text="Filtering...">
                    <i class="bi bi-search me-1"></i> Search
                </button>
                <a class="btn btn-outline-secondary" href="{{ route('users.index') }}">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>

    <div class="table-panel">
        <div class="table-panel-header">
            <div>
                <div class="table-panel-eyebrow mb-1">Directory</div>
                <h3 class="table-panel-title mb-0">User table</h3>
            </div>
            <span class="badge badge-soft">{{ $users->total() }} total</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Team</th>
                        <th>Role</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>
                                <a href="{{ route('users.show', $user) }}" class="fw-semibold text-decoration-none">{{ $user->name }}</a>
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->team?->name ?: 'No team' }}</td>
                            <td class="text-capitalize">{{ $user->role }}</td>
                            <td class="text-end">
                                @can('update', $user)
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('users.edit', $user) }}">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endcan
                                @can('delete', $user)
                                    @if (auth()->id() !== $user->id)
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-danger"
                                            data-delete-confirm
                                            data-delete-action="{{ route('users.destroy', $user) }}"
                                            data-delete-title="Delete User"
                                            data-delete-message="Are you sure you want to delete {{ $user->name }}? This action cannot be undone."
                                            data-delete-submit="Delete User"
                                        >
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="table-empty">No users found for the current filters.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $users->links('pagination::bootstrap-5') }}</div>
</x-app-layout>
