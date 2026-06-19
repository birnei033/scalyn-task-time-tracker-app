<section class="card profile-card shadow-sm border-0 h-100">
    <div class="card-body p-4 p-lg-5">
        <header class="mb-4">
            <div class="profile-section-kicker">{{ __('Security') }}</div>
            <h2 class="profile-section-title mb-2">
                {{ __('Update Password') }}
            </h2>

            <p class="profile-section-copy mb-0">
                {{ __('Ensure your account is using a long, random password to stay secure.') }}
            </p>
        </header>

        <form method="post" action="{{ route('password.update') }}" class="profile-form">
            @csrf
            @method('put')

            <div class="mb-3">
                <x-input-label for="update_password_current_password" :value="__('Current Password')" class="profile-label" required />
                <x-text-input id="update_password_current_password" name="current_password" type="password" class="form-control profile-input mt-1" autocomplete="current-password" />
                <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
            </div>

            <div class="mb-3">
                <x-input-label for="update_password_password" :value="__('New Password')" class="profile-label" required />
                <x-text-input id="update_password_password" name="password" type="password" class="form-control profile-input mt-1" autocomplete="new-password" />
                <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
            </div>

            <div class="mb-4">
                <x-input-label for="update_password_password_confirmation" :value="__('Confirm Password')" class="profile-label" required />
                <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="form-control profile-input mt-1" autocomplete="new-password" />
                <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
            </div>

            <div class="profile-actions d-flex flex-wrap align-items-center gap-3">
                <x-primary-button class="btn btn-primary profile-action-button">{{ __('Save password') }}</x-primary-button>

                @if (session('status') === 'password-updated')
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
