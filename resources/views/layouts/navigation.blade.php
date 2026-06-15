@php
    $items = [
        ['route' => 'dashboard', 'icon' => 'speedometer2', 'label' => 'Dashboard'],
        ['route' => 'clients.index', 'icon' => 'building', 'label' => 'Clients'],
        ['route' => 'tasks.index', 'icon' => 'list-check', 'label' => 'Tasks'],
        ['route' => 'time-entries.index', 'icon' => 'clock-history', 'label' => 'Time Entry'],
        ['route' => 'timesheets.index', 'icon' => 'calendar-week', 'label' => 'Timesheets'],
    ];

    if (auth()->user()->canManageTeam()) {
        $items[] = ['route' => 'users.index', 'icon' => 'people', 'label' => 'Users'];
        $items[] = ['route' => 'reports.index', 'icon' => 'bar-chart', 'label' => 'Reports'];
        $items[] = ['route' => 'team.index', 'icon' => 'people', 'label' => 'Team Management'];
    }
@endphp

<aside class="sidebar px-3 py-3 py-lg-4">
    <div class="d-flex align-items-center justify-content-between gap-3 mb-4">
        <a href="{{ route('dashboard') }}" class="sidebar-brand text-decoration-none">
            <span class="sidebar-brand-mark">
                <x-application-logo class="brand-mark" />
            </span>
            <span class="d-flex flex-column lh-sm">
                <span class="sidebar-brand-title">Scalyn</span>
                <span class="sidebar-brand-subtitle">Task Time Tracker</span>
            </span>
        </a>

        <button type="button" class="btn btn-outline-light btn-sm sidebar-close d-lg-none" data-sidebar-toggle>
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <div class="sidebar-section-label mb-2 px-2">Workspace</div>

    <nav class="nav nav-pills flex-column gap-1">
        @foreach ($items as $item)
            <a class="nav-link {{ request()->routeIs($item['route']) ? 'active' : '' }}" href="{{ route($item['route']) }}">
                <i class="bi bi-{{ $item['icon'] }}"></i>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach

        <a class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.edit') }}">
            <i class="bi bi-person-circle"></i>
            <span>Profile</span>
        </a>
    </nav>

    <div class="sidebar-footer mt-4 p-3">
        <div class="small text-white-50 mb-1">Signed in as</div>
        <div class="fw-semibold text-white">{{ auth()->user()->name }}</div>
        <div class="small text-white-50 text-capitalize">{{ auth()->user()->role }}</div>
    </div>
</aside>
