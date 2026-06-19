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
    <main class="guest-shell d-flex align-items-center py-4 py-lg-5">
        <div class="container guest-panel">
            <div class="row align-items-stretch g-4 g-lg-5">
                <div class="col-lg-5 d-flex">
                    <div class="guest-brand-panel w-100">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <span class="guest-brand-mark">
                                <x-application-logo class="brand-mark" />
                            </span>
                            <div>
                                <div class="guest-kicker mb-1">Scalyn</div>
                                <h1 class="guest-title h3 mb-0">Task Time Tracker</h1>
                            </div>
                        </div>

                        <p class="guest-brand-copy mb-4">
                            Track client work, review time, and keep projects moving with a calm, responsive workspace built around Scalyn's blue brand system.
                        </p>

                        <div class="guest-metric-grid mb-4">
                            <div class="guest-metric">
                                <div class="value">Desktop</div>
                                <div class="label">Focused admin workspace</div>
                            </div>
                            <div class="guest-metric">
                                <div class="value">Mobile</div>
                                <div class="label">Fast updates on the go</div>
                            </div>
                            <div class="guest-metric">
                                <div class="value">Teams</div>
                                <div class="label">Shared visibility</div>
                            </div>
                            <div class="guest-metric">
                                <div class="value">Reports</div>
                                <div class="label">Clear operational insight</div>
                            </div>
                        </div>

                        <div class="brand-pill">
                            <i class="bi bi-shield-check"></i>
                            Clean UI, clear validation, and practical workflows
                        </div>
                    </div>
                </div>

                <div class="col-lg-7 d-flex">
                    <div class="guest-card w-100">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
