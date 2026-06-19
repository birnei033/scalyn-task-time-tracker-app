@php($user = $user ?? new \App\Models\User)

<div class="row g-3">
    <div class="col-lg-6">
        <label class="form-label">Name <x-required-indicator /></label>
        <input class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $user->name) }}" required>
        @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-lg-6">
        <label class="form-label">Email <x-required-indicator /></label>
        <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $user->email) }}" required>
        @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-lg-4">
        <label class="form-label">Role <x-required-indicator /></label>
        <select class="form-select @error('role') is-invalid @enderror" name="role" required>
            @foreach ($roles as $value => $label)
                <option value="{{ $value }}" @selected(old('role', $user->role ?: 'member') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('role')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-lg-4">
        <label class="form-label">Team</label>
        <select class="form-select @error('team_id') is-invalid @enderror" name="team_id">
            <option value="">No team</option>
            @foreach ($teams as $team)
                <option value="{{ $team->id }}" @selected((string) old('team_id', $user->team_id) === (string) $team->id)>{{ $team->name }}</option>
            @endforeach
        </select>
        @error('team_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-lg-4">
        <label class="form-label">Password @if (! $user->exists)<x-required-indicator />@endif</label>
        <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" {{ $user->exists ? '' : 'required' }}>
        <div class="form-text">
            {{ $user->exists ? 'Leave blank to keep the current password.' : 'Use a strong password for the new account.' }}
        </div>
        @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-lg-4">
        <label class="form-label">Confirm Password @if (! $user->exists)<x-required-indicator />@endif</label>
        <input type="password" class="form-control" name="password_confirmation" {{ $user->exists ? '' : 'required' }}>
    </div>
</div>
