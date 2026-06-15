<!DOCTYPE html>
@php
    $themePreference = auth()->user()?->theme_preference;
@endphp
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    data-theme-source="account"
    data-theme-preference="{{ $themePreference ?? 'system' }}"
>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Scalyn Task Time Tracker') }}</title>
    <x-theme-init source="account" :preference="$themePreference" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="app-shell">
        @include('layouts.navigation')
        <div class="sidebar-backdrop d-lg-none" aria-hidden="true"></div>

        <div class="content-area">
            <header class="app-topbar">
                <div class="page-shell py-3 py-lg-3">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <button type="button" class="btn btn-outline-secondary btn-sm sidebar-toggle d-lg-none" data-sidebar-toggle>
                                <i class="bi bi-list"></i>
                            </button>

                            <div class="d-flex align-items-center gap-3">
                                <span class="sidebar-brand-mark d-none d-sm-inline-flex" style="width: 44px; height: 44px;">
                                    <x-application-logo class="brand-mark" />
                                </span>
                                <div class="d-flex flex-column gap-2">
                                    <div class="page-kicker mb-1">Scalyn Task Time Tracker</div>
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <h1 class="page-title h4 mb-0">{{ $header ?? 'Dashboard' }}</h1>

                                        @isset($actions)
                                            <div class="d-flex flex-wrap align-items-center gap-2">
                                                {{ $actions }}
                                            </div>
                                        @endisset
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap align-items-center justify-content-end gap-2">
                            <form method="POST" action="{{ route('profile.update') }}" class="d-inline-flex align-items-center">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="name" value="{{ auth()->user()->name }}">
                                <input type="hidden" name="email" value="{{ auth()->user()->email }}">
                                <input type="hidden" name="theme_preference" value="{{ $themePreference ?? 'system' }}" data-theme-preference-input>
                                <x-theme-toggle type="submit" />
                            </form>
                            <span class="badge badge-soft text-capitalize">{{ auth()->user()->role }}</span>
                            <span class="badge text-bg-light border d-none d-md-inline-flex">{{ auth()->user()->name }}</span>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="btn btn-outline-secondary btn-sm" type="submit" data-loading-text="Signing out...">
                                    <i class="bi bi-box-arrow-right me-1"></i>
                                    Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <main class="page-shell">
                @if (session('status'))
                    <div class="alert alert-success shadow-sm border-0">{{ session('status') }}</div>
                @endif

                {{ $slot }}
            </main>
        </div>

        <x-delete-confirmation-modal />
    </div>
</body>
</html>
