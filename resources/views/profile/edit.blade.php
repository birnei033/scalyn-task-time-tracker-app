<x-app-layout>
    <x-slot name="header">
        <div class="profile-page-header">
            <div class="text-muted small text-uppercase letter-spacing-wide">
                {{ __('Account settings') }}
            </div>
            <h2 class="profile-page-title mb-0">
                {{ __('Profile') }}
            </h2>
        </div>
    </x-slot>

    <div class="profile-page py-4 py-lg-5">
        <div class="container-fluid px-4 px-lg-5">
            <div class="profile-hero card border-0 shadow-sm mb-4">
                <div class="card-body p-4 p-lg-5">
                    <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4">
                        <div class="d-flex align-items-start gap-3">
                            <div class="profile-hero-icon">
                                <i class="bi bi-person-badge"></i>
                            </div>
                            <div>
                                <div class="profile-section-kicker">{{ __('Scalyn Task Time Tracker') }}</div>
                                <h3 class="profile-hero-title mb-2">{{ __('Keep your account details current') }}</h3>
                                <p class="profile-hero-copy mb-0">
                                    {{ __('Update your profile information, change your password, and manage your account securely from one place.') }}
                                </p>
                            </div>
                        </div>
                        <div class="profile-hero-meta">
                            <div class="profile-meta-item">
                                <span class="profile-meta-label">{{ __('Role') }}</span>
                                <span class="profile-meta-value text-capitalize">{{ auth()->user()->role }}</span>
                            </div>
                            <div class="profile-meta-item">
                                <span class="profile-meta-label">{{ __('Email') }}</span>
                                <span class="profile-meta-value">{{ $user->email }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-12 col-xl-6">
                    @include('profile.partials.update-profile-information-form')
                </div>

                <div class="col-12 col-xl-6">
                    @include('profile.partials.update-password-form')
                </div>

                <div class="col-12">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
