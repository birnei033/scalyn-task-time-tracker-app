<section class="card profile-card shadow-sm border-0 h-100">
    <div class="card-body p-4 p-lg-5">
        <header class="mb-4">
            <div class="profile-section-kicker">{{ __('Personal details') }}</div>
            <h2 class="profile-section-title mb-2">
                {{ __('Profile Information') }}
            </h2>

            <p class="profile-section-copy mb-0">
                {{ __("Update your account's profile information and email address.") }}
            </p>
        </header>

        <form id="send-verification" method="post" action="{{ route('verification.send') }}">
            @csrf
        </form>

        <form method="post" action="{{ route('profile.update') }}" class="profile-form">
            @csrf
            @method('patch')

            <div class="mb-3">
                <x-input-label for="name" :value="__('Name')" class="profile-label" />
                <x-text-input id="name" name="name" type="text" class="form-control profile-input mt-1" :value="old('name', $user->name)" required autofocus autocomplete="name" />
                <x-input-error class="mt-2" :messages="$errors->get('name')" />
            </div>

            <div class="mb-4">
                <x-input-label for="email" :value="__('Email')" class="profile-label" />
                <x-text-input id="email" name="email" type="email" class="form-control profile-input mt-1" :value="old('email', $user->email)" required autocomplete="username" />
                <x-input-error class="mt-2" :messages="$errors->get('email')" />

                @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                    <div class="profile-verification-callout mt-3">
                        <div class="profile-verification-icon">
                            <i class="bi bi-shield-exclamation"></i>
                        </div>
                        <div>
                            <div class="profile-verification-title">{{ __('Your email address is unverified.') }}</div>
                            <button form="send-verification" class="btn btn-link p-0 profile-link-button">
                                {{ __('Click here to re-send the verification email.') }}
                            </button>

                            @if (session('status') === 'verification-link-sent')
                                <div class="profile-status status-success mt-2">
                                    {{ __('A new verification link has been sent to your email address.') }}
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <div class="mb-4">
                <x-input-label :value="__('Appearance')" class="profile-label" />
                @php
                    $themePreference = old('theme_preference', $user->theme_preference ?? 'system');
                @endphp
                <div class="theme-preference-group" role="radiogroup" aria-label="{{ __('Appearance') }}">
                    <input
                        class="btn-check"
                        type="radio"
                        name="theme_preference"
                        id="theme-preference-system"
                        value="system"
                        @checked($themePreference === 'system')
                    >
                    <label class="btn btn-outline-secondary theme-preference-option" for="theme-preference-system">
                        <i class="bi bi-circle-half"></i>
                        <span>{{ __('System') }}</span>
                    </label>

                    <input
                        class="btn-check"
                        type="radio"
                        name="theme_preference"
                        id="theme-preference-light"
                        value="light"
                        @checked($themePreference === 'light')
                    >
                    <label class="btn btn-outline-secondary theme-preference-option" for="theme-preference-light">
                        <i class="bi bi-sun-fill"></i>
                        <span>{{ __('Light') }}</span>
                    </label>

                    <input
                        class="btn-check"
                        type="radio"
                        name="theme_preference"
                        id="theme-preference-dark"
                        value="dark"
                        @checked($themePreference === 'dark')
                    >
                    <label class="btn btn-outline-secondary theme-preference-option" for="theme-preference-dark">
                        <i class="bi bi-moon-stars-fill"></i>
                        <span>{{ __('Night') }}</span>
                    </label>
                </div>
                <div class="form-text mt-2">
                    {{ __('System follows your device theme. Light and night stay pinned to this account.') }}
                </div>
                <x-input-error class="mt-2" :messages="$errors->get('theme_preference')" />
            </div>

            <div class="profile-actions d-flex flex-wrap align-items-center gap-3">
                <x-primary-button class="btn btn-primary profile-action-button">{{ __('Save changes') }}</x-primary-button>

                @if (session('status') === 'profile-updated')
                    <p
                        x-data="{ show: true }"
                        x-show="show"
                        x-transition
                        x-init="setTimeout(() => show = false, 2000)"
                        class="profile-status mb-0"
                    >{{ __('Saved.') }}</p>
                @endif
            </div>
        </form>
    </div>
</section>
