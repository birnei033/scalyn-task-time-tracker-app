<x-guest-layout>
    <div class="mb-4">
        <div class="page-kicker mb-2">Create your account</div>
        <h2 class="guest-title h4 mb-2">Get started with Scalyn</h2>
        <p class="guest-copy mb-0">
            Set up your workspace to manage clients, assign work, and track time without the clutter.
        </p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="mt-4">
        @csrf

        <div class="mb-3">
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="w-100 @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div class="mb-3">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="w-100 @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mb-3">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="w-100 @error('password') is-invalid @enderror" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mb-3">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation" class="w-100 @error('password_confirmation') is-invalid @enderror" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <x-primary-button class="w-100 justify-content-center" data-loading-text="Creating account...">
            {{ __('Register') }}
        </x-primary-button>

        <div class="text-center mt-3 small">
            <span class="text-muted">Already registered?</span>
            <a href="{{ route('login') }}" class="text-decoration-none fw-semibold">Sign in</a>
        </div>
    </form>
</x-guest-layout>
