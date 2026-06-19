<x-guest-layout>
    <div class="mb-4">
        <div class="page-kicker mb-2">Password help</div>
        <h2 class="guest-title h4 mb-2">Reset your password</h2>
        <p class="guest-copy mb-0">
            Enter your email address and we will send you a reset link.
        </p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="mb-3">
            <x-input-label for="email" :value="__('Email')" required />
            <x-text-input id="email" class="w-100 @error('email') is-invalid @enderror" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <x-primary-button class="w-100 justify-content-center" data-loading-text="Sending link...">
            {{ __('Email Password Reset Link') }}
        </x-primary-button>
    </form>
</x-guest-layout>
