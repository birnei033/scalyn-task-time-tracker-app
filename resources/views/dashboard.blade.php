<x-app-layout>
    <x-slot name="header">Dashboard</x-slot>

    <section class="page-hero p-4 p-lg-5 mb-4">
        <div class="row align-items-end g-4">
            <div class="col-lg-8">
                <div class="page-kicker mb-2">Overview</div>
                <h2 class="page-title display-6 mb-3">Your workspace at a glance.</h2>
                <p class="page-subtitle mb-0">
                    Monitor hours, tasks, and client activity from a responsive dashboard built around Scalyn's blue theme.
                </p>
            </div>

            <div class="col-lg-4">
                <div class="hero-metric ms-lg-auto">
                    <div class="label mb-1">This week</div>
                    <div class="value">{{ number_format((float) $weekHours, 2) }} hrs</div>
                    <div class="stat-mini mt-2">Tracked across the current workspace scope</div>
                </div>
            </div>
        </div>
    </section>

    <section class="row g-3 mb-4">
        @foreach ([
            ['label' => 'Total Hours', 'value' => number_format((float) $totalHours, 2), 'icon' => 'clock-history', 'note' => 'All logged work'],
            ['label' => 'This Week', 'value' => number_format((float) $weekHours, 2), 'icon' => 'calendar-week', 'note' => 'Current period'],
            ['label' => 'Active Clients', 'value' => $activeClients, 'icon' => 'building', 'note' => 'Open accounts'],
            ['label' => 'Open Tasks', 'value' => $openTasks, 'icon' => 'list-check', 'note' => 'Pending work'],
        ] as $metric)
            <div class="col-sm-6 col-xl-3">
                <article class="metric-card">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <div class="metric-label">{{ $metric['label'] }}</div>
                            <div class="metric-value mt-1">{{ $metric['value'] }}</div>
                            <div class="metric-copy mt-2">{{ $metric['note'] }}</div>
                        </div>
                        <div class="metric-icon">
                            <i class="bi bi-{{ $metric['icon'] }}"></i>
                        </div>
                    </div>
                </article>
            </div>
        @endforeach

        @if ($teamMembers !== null)
            <div class="col-sm-6 col-xl-3">
                <article class="metric-card">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <div class="metric-label">Team Members</div>
                            <div class="metric-value mt-1">{{ $teamMembers }}</div>
                            <div class="metric-copy mt-2">Users in the workspace</div>
                        </div>
                        <div class="metric-icon">
                            <i class="bi bi-people"></i>
                        </div>
                    </div>
                </article>
            </div>
        @endif
    </section>

    <section class="row g-4">
        <div class="col-xl-7">
            <div class="table-panel">
                <div class="table-panel-header">
                    <div>
                        <div class="table-panel-eyebrow mb-1">Activity</div>
                        <h3 class="table-panel-title mb-0">Recent time entries</h3>
                    </div>
                    <span class="badge badge-soft">{{ $recentEntries->count() }} entries</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Client / Task</th>
                                <th>User</th>
                                <th class="text-end">Hours</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentEntries as $entry)
                                <tr>
                                    <td>{{ $entry->date->format('M d, Y') }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $entry->task->client->name }}</div>
                                        <div class="small text-muted">{{ $entry->task->title }}</div>
                                    </td>
                                    <td>{{ $entry->user->name }}</td>
                                    <td class="text-end fw-semibold">{{ number_format((float) $entry->hours, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">
                                        <div class="table-empty">
                                            No time entries yet. Once people start logging time, activity will appear here.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="surface-card p-4 h-100">
                <div class="d-flex align-items-center justify-content-between gap-3 mb-4">
                    <div>
                        <div class="section-kicker mb-1">Insights</div>
                        <h3 class="h5 mb-0">Top clients by hours</h3>
                    </div>
                    <i class="bi bi-bar-chart-line stat-icon"></i>
                </div>

                <div class="d-grid gap-3">
                    @forelse ($clientHours as $row)
                        @php($maxHours = max(1, (float) $clientHours->max('hours')))
                        <div>
                            <div class="d-flex align-items-center justify-content-between gap-3 mb-2">
                                <div>
                                    <div class="fw-semibold">{{ $row->name }}</div>
                                    <div class="small text-muted">{{ number_format((float) $row->hours, 2) }} hrs</div>
                                </div>
                                <span class="badge badge-soft">{{ round(((float) $row->hours / $maxHours) * 100) }}%</span>
                            </div>
                            <div class="progress progress-soft">
                                <div class="progress-bar" style="width: {{ round(((float) $row->hours / $maxHours) * 100) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="muted-copy mb-0">Client totals will appear after time is logged.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
