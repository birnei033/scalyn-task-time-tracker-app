<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Scalyn Task Time Tracker') }}</title>
    <link rel="icon" type="image/png" sizes="256x256" href="{{ asset('favicon.png') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <x-theme-init />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="app-shell">
        @include('layouts.navigation')
        <div class="sidebar-backdrop d-lg-none" aria-hidden="true"></div>

        <div class="content-area">
            <header class="app-topbar">
                <div class="page-shell py-3 py-lg-3">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 app-topbar-row">
                        <div class="d-flex align-items-center gap-3 min-w-0">
                            <button type="button" class="btn btn-outline-secondary btn-sm sidebar-toggle d-lg-none" data-sidebar-toggle>
                                <i class="bi bi-list"></i>
                            </button>

                            <div class="d-flex align-items-center gap-3 min-w-0">
                                <span class="app-topbar-brand-mark d-none d-sm-inline-flex">
                                    <x-application-logo class="brand-mark" />
                                </span>
                                <div class="d-flex flex-column min-w-0">
                                    <div class="page-kicker app-topbar-kicker mb-1">Scalyn Task Time Tracker</div>
                                    <h1 class="page-title app-topbar-title h4 mb-0">{{ $header ?? 'Dashboard' }}</h1>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap align-items-center justify-content-end gap-2 app-topbar-controls">
                            @isset($actions)
                                <div class="d-flex flex-wrap align-items-center gap-2 app-topbar-actions">
                                    {{ $actions }}
                                </div>
                            @endisset

                            <span class="badge badge-soft text-capitalize app-pill">{{ auth()->user()->role }}</span>
                            <span class="badge text-bg-light border app-pill d-none d-md-inline-flex">{{ auth()->user()->name }}</span>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="btn btn-outline-secondary btn-sm app-pill" type="submit" data-loading-text="Signing out...">
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
