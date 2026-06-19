<x-guest-layout>
    <div class="mb-4">
        <div class="page-kicker mb-2">Secure reset</div>
        <h2 class="guest-title h4 mb-2">Choose a new password</h2>
        <p class="guest-copy mb-0">
            Use a strong password that you can remember on all of your devices.
        </p>
    </div>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="mb-3">
            <x-input-label for="email" :value="__('Email')" required />
            <x-text-input id="email" class="w-100 @error('email') is-invalid @enderror" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mb-3">
            <x-input-label for="password" :value="__('Password')" required />
            <x-text-input id="password" class="w-100 @error('password') is-invalid @enderror" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mb-3">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" required />
            <x-text-input id="password_confirmation" class="w-100 @error('password_confirmation') is-invalid @enderror" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <x-primary-button class="w-100 justify-content-center" data-loading-text="Resetting password...">
            {{ __('Reset Password') }}
        </x-primary-button>
    </form>
</x-guest-layout>
