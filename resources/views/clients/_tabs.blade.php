<div class="surface-card p-3 p-lg-4 mb-4 task-detail-tabs">
    <ul class="nav nav-tabs task-tabs client-tabs" id="client-tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <a
                class="nav-link {{ $activeTab === 'clients' ? 'active' : '' }}"
                href="{{ route('clients.index') }}"
            >
                Clients
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a
                class="nav-link {{ $activeTab === 'archives' ? 'active' : '' }}"
                href="{{ route('clients.archives') }}"
            >
                Archives
            </a>
        </li>
    </ul>
</div>
