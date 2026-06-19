<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    <link rel="icon" type="image/png" sizes="256x256" href="{{ asset('favicon.png') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <x-theme-init />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main class="guest-shell d-flex align-items-center py-4 py-lg-5">
        <div class="container guest-panel">
            <div class="page-hero p-4 p-lg-5 mb-4">
                <div class="row align-items-center g-4">
                    <div class="col-lg-7">
                        <div class="brand-pill mb-3">
                            <x-application-logo class="brand-mark" style="width: 20px; height: 20px;" />
                            Scalyn Task Time Tracker
                        </div>
                        <div class="page-kicker mb-2">UI/UX milestone complete</div>
                        <h1 class="page-title display-5 mb-3">A cleaner, faster way to track time and manage work.</h1>
                        <p class="page-subtitle lead mb-4">
                            The dashboard, forms, and tables have been redesigned around Scalyn's blue brand palette with responsive layouts for desktop, tablet, and mobile.
                        </p>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('login') }}" class="btn btn-primary btn-lg">Login</a>
                            <a href="{{ route('register') }}" class="btn btn-outline-secondary btn-lg">Register</a>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="surface-card p-4">
                            <div class="d-flex justify-content-between align-items-start gap-3 border-bottom pb-3 mb-3">
                                <div>
                                    <div class="stat-label mb-1">Dashboard cards</div>
                                    <div class="stat-value">Responsive</div>
                                </div>
                                <div class="stat-icon">
                                    <i class="bi bi-grid-1x2-fill"></i>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between border-bottom pb-3 mb-3">
                                <span class="stat-copy">Statistics widgets</span>
                                <strong>Branded</strong>
                            </div>
                            <div class="d-flex justify-content-between border-bottom pb-3 mb-3">
                                <span class="stat-copy">Validation states</span>
                                <strong>Clear</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="stat-copy">Loading feedback</span>
                                <strong>Included</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
