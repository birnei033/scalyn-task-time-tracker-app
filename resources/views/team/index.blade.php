<x-app-layout>
    <x-slot name="header">Team Management</x-slot>
    <x-slot name="actions">
        <a class="btn btn-outline-secondary btn-lg" href="{{ route('users.index') }}">
            <i class="bi bi-people me-1"></i> Open Users
        </a>
    </x-slot>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="surface-card p-4 h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <div class="section-kicker mb-1">Groups</div>
                        <h3 class="h5 mb-0">Teams</h3>
                    </div>
                    <i class="bi bi-diagram-3 stat-icon"></i>
                </div>
                <div class="d-grid gap-2">
                    @forelse ($teams as $team)
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2">
                            <span>{{ $team->name }}</span>
                            <span class="badge badge-soft">{{ $team->users_count }} users</span>
                        </div>
                    @empty
                        <p class="muted-copy mb-0">No teams found.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="table-panel">
                <div class="table-panel-header">
                    <div>
                        <div class="table-panel-eyebrow mb-1">Members</div>
                        <h3 class="table-panel-title mb-0">Users</h3>
                    </div>
                    <span class="badge badge-soft">{{ $users->count() }} users</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Team</th>
                                <th>Role</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $user)
                                <tr>
                                    <td>
                                        <x-user-identity :name="$user->name" seed="{{ $user->id }}" />
                                    </td>
                                    <td>{{ $user->email }}</td>
                                    <td>{{ $user->team?->name ?: 'No team' }}</td>
                                    <td class="text-capitalize">{{ $user->role }}</td>
                                    <td class="text-end">
                                        @if (auth()->user()->canManageTeam())
                                            <form class="d-flex flex-wrap gap-2 justify-content-end" method="POST" action="{{ route('team.users.update', $user) }}">
                                                @csrf
                                                @method('PATCH')
                                                <select class="form-select form-select-sm" name="role" style="max-width: 130px;">
                                                    @foreach (['admin', 'manager', 'member'] as $role)
                                                        <option value="{{ $role }}" @selected($user->role === $role)>{{ ucfirst($role) }}</option>
                                                    @endforeach
                                                </select>
                                                <select class="form-select form-select-sm" name="team_id" style="max-width: 180px;">
                                                    <option value="">No team</option>
                                                    @foreach ($teams as $team)
                                                        <option value="{{ $team->id }}" @selected($user->team_id === $team->id)>{{ $team->name }}</option>
                                                    @endforeach
                                                </select>
                                                <button class="btn btn-outline-primary table-action-icon-btn table-action-primary" data-loading-text="Saving..." aria-label="Save {{ $user->name }}">
                                                    <i class="bi bi-save"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <div class="table-empty">No users found.</div>
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
