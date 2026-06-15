<section class="card profile-card profile-card-danger shadow-sm border-0">
    <div class="card-body p-4 p-lg-5">
        <header class="mb-4">
            <div class="profile-section-kicker text-danger">{{ __('Danger zone') }}</div>
            <h2 class="profile-section-title mb-2">
                {{ __('Delete Account') }}
            </h2>

            <p class="profile-section-copy mb-0">
                {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
            </p>
        </header>

        <div class="profile-danger-panel">
            <div class="profile-danger-icon">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <div class="flex-grow-1">
                <div class="profile-danger-title">{{ __('This action permanently removes your account.') }}</div>
                <p class="profile-danger-copy mb-0">
                    {{ __('You will be signed out immediately and all related data will be deleted after confirmation.') }}
                </p>
            </div>
            <x-danger-button
                class="btn btn-danger profile-danger-button"
                data-bs-toggle="modal"
                data-bs-target="#confirm-user-deletion"
            >{{ __('Delete Account') }}</x-danger-button>
        </div>

        <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()">
            <form method="post" action="{{ route('profile.destroy') }}">
                @csrf
                @method('delete')

                <div class="modal-header profile-modal-header">
                    <div>
                        <div class="profile-modal-kicker">{{ __('Confirm deletion') }}</div>
                        <h2 class="modal-title fs-5 mb-0">
                            {{ __('Are you sure you want to delete your account?') }}
                        </h2>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>

                <div class="modal-body profile-modal-body">
                    <div class="profile-modal-warning">
                        <i class="bi bi-shield-exclamation"></i>
                        <div>
                            <div class="profile-modal-warning-title">{{ __('This cannot be undone.') }}</div>
                            <p class="profile-modal-warning-copy mb-0">
                                {{ __('Please enter your password to confirm you would like to permanently delete your account.') }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-4">
                        <x-input-label for="password" value="{{ __('Password') }}" class="profile-label visually-hidden" />

                        <x-text-input
                            id="password"
                            name="password"
                            type="password"
                            class="form-control profile-input"
                            placeholder="{{ __('Password') }}"
                        />

                        <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
                    </div>
                </div>

                <div class="modal-footer profile-modal-footer">
                    <x-secondary-button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        {{ __('Cancel') }}
                    </x-secondary-button>

                    <x-danger-button class="btn btn-danger ms-3">
                        {{ __('Delete Account') }}
                    </x-danger-button>
                </div>
            </form>
        </x-modal>
    </div>
</section>
