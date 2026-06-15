<x-guest-layout>
    <div class="mb-4">
        <div class="page-kicker mb-2">Welcome back</div>
        <h2 class="guest-title h4 mb-2">Sign in to your workspace</h2>
        <p class="guest-copy mb-0">
            Use your account to access dashboards, task tracking, reports, and team operations.
        </p>
    </div>

    @if (session('status'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="mt-4">
        @csrf

        <div class="mb-3">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="w-100 @error('email') is-invalid @enderror" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mb-3">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="w-100 @error('password') is-invalid @enderror" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remember" id="remember_me">
                <label class="form-check-label" for="remember_me">Remember me</label>
            </div>

            @if (Route::has('password.request'))
                <a class="small text-decoration-none" href="{{ route('password.request') }}">Forgot password?</a>
            @endif
        </div>

        <x-primary-button class="w-100 justify-content-center" data-loading-text="Signing in...">
            {{ __('Login') }}
        </x-primary-button>

        <div class="text-center mt-3 small">
            <span class="text-muted">Need an account?</span>
            <a href="{{ route('register') }}" class="text-decoration-none fw-semibold">Create one</a>
        </div>
    </form>
</x-guest-layout>
