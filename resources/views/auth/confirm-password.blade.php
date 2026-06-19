<x-guest-layout>
    <div class="mb-4">
        <div class="page-kicker mb-2">Confirm identity</div>
        <h2 class="guest-title h4 mb-2">Verify your password</h2>
        <p class="guest-copy mb-0">
            This protects sensitive actions inside your workspace.
        </p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div class="mb-3">
            <x-input-label for="password" :value="__('Password')" required />
            <x-text-input id="password" class="w-100 @error('password') is-invalid @enderror" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="d-flex justify-content-end">
            <x-primary-button data-loading-text="Confirming...">
                {{ __('Confirm') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
