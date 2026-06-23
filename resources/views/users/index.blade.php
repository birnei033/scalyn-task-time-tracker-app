<x-app-layout>
    <x-slot name="header">Users</x-slot>
    <x-slot name="actions">
        @can('create', App\Models\User::class)
            <a href="{{ route('users.create') }}" class="btn btn-primary btn-lg">
                <i class="bi bi-person-plus me-1"></i> Add User
            </a>
        @endcan
    </x-slot>

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
                                        <x-user-identity :name="$user->name" :href="route('users.show', $user)" seed="{{ $user->id }}" />
                                    </td>
                                    <td>{{ $user->email }}</td>
                                    <td>{{ $user->team?->name ?: 'No team' }}</td>
                            <td class="text-capitalize">{{ $user->role }}</td>
                            <td class="text-end">
                                @can('update', $user)
                                    <a class="btn btn-outline-secondary table-action-icon-btn table-action-view" href="{{ route('users.edit', $user) }}" aria-label="Edit {{ $user->name }}">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endcan
                                @can('delete', $user)
                                    @if (auth()->id() !== $user->id)
                                        <button
                                            type="button"
                                            class="btn btn-outline-danger table-action-icon-btn table-action-delete"
                                            data-delete-confirm
                                            data-delete-action="{{ route('users.destroy', $user) }}"
                                            data-delete-title="Delete User"
                                            data-delete-message="Are you sure you want to delete {{ $user->name }}? This action cannot be undone."
                                            data-delete-submit="Delete User"
                                            aria-label="Delete {{ $user->name }}"
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
