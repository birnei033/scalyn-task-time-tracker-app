<x-guest-layout>
    <div class="mb-4">
        <div class="page-kicker mb-2">Verify email</div>
        <h2 class="guest-title h4 mb-2">Check your inbox</h2>
        <p class="guest-copy mb-0">
            We sent a verification link to finish activating your account.
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="alert alert-success border-0 shadow-sm">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mt-4">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button data-loading-text="Sending...">
                {{ __('Resend Verification Email') }}
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-outline-secondary" data-loading-text="Logging out...">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-guest-layout>
