<x-app-layout>
    <x-slot name="header">Reports</x-slot>

    <section class="page-hero p-4 p-lg-5 mb-4">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <div class="page-kicker mb-2">Reporting</div>
                <h2 class="page-title h1 mb-3">Summaries that stay readable and exportable.</h2>
                <p class="page-subtitle mb-0">
                    Filter the data set and compare client, task, and employee totals in branded report panels.
                </p>
            </div>
        </div>
    </section>

    <div class="surface-card p-4 mb-4">
        <form class="row g-3 align-items-end" method="GET" data-relative-date-range-form>
            <div class="col-lg-2">
                <label class="form-label">View</label>
                <select class="form-select" name="view" data-relative-date-range-view-select>
                    <option value="daily" @selected($view === 'daily')>Daily</option>
                    <option value="weekly" @selected($view === 'weekly')>Weekly</option>
                    <option value="monthly" @selected($view === 'monthly')>Monthly</option>
                </select>
            </div>
            <div class="col-lg-2">
                <label class="form-label">From</label>
                <input type="date" class="form-control" name="from" value="{{ $from }}" data-relative-date-range-from-input>
            </div>
            <div class="col-lg-2">
                <label class="form-label">To</label>
                <input type="date" class="form-control" name="to" value="{{ $to }}" data-relative-date-range-to-input>
            </div>
            <div class="col-lg-2">
                <label class="form-label">Client</label>
                <select class="form-select" name="client_id">
                    <option value="">All clients</option>
                    @foreach ($clients as $client)
                        <option value="{{ $client->id }}" @selected(request('client_id') == $client->id)>{{ $client->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2">
                <label class="form-label">User</label>
                <select class="form-select" name="user_id">
                    <option value="">All users</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary" data-loading-text="Filtering...">
                    <i class="bi bi-funnel me-1"></i> Apply filters
                </button>
                <a class="btn btn-outline-secondary" href="{{ route('reports.index') }}">Reset</a>
            </div>
        </form>
    </div>

    <div class="surface-card reports-total-card p-4 mb-4">
        <div class="metric-label">Filtered Total</div>
        <div class="metric-value mt-1">{{ \App\Support\TimeDisplay::formatHours($totalHours) }}</div>
        <div class="metric-copy mt-2">Total logged time within the selected range and filters.</div>
    </div>

    <div class="row g-4">
        @foreach ([['key' => 'clientHours', 'title' => 'Time per Client', 'rows' => $clientHours, 'name' => 'name', 'exportLabel' => 'Export client time as PDF or CSV'], ['key' => 'taskHours', 'title' => 'Time per Task', 'rows' => $taskHours, 'name' => 'title', 'exportLabel' => 'Export task time as PDF or CSV'], ['key' => 'userHours', 'title' => 'Time per Employee', 'rows' => $userHours, 'name' => 'name', 'exportLabel' => 'Export employee time as PDF or CSV']] as $report)
            <div class="col-lg-4">
                <div class="table-panel h-100">
                    <div class="table-panel-header">
                        <div>
                            <div class="table-panel-eyebrow mb-1">Breakdown</div>
                            <h3 class="table-panel-title mb-0">{{ $report['title'] }}</h3>
                        </div>
                        <button
                            type="button"
                            class="stat-icon stat-icon-labeled stat-icon-button"
                            data-report-export-trigger
                            data-report-export-report="{{ $report['key'] }}"
                            data-report-export-title="{{ $report['title'] }}"
                            aria-label="{{ $report['exportLabel'] }}"
                            title="{{ $report['exportLabel'] }}"
                        >
                            <span>Export</span>
                            <i class="bi bi-download"></i>
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>{{ $report['name'] === 'title' ? 'Task' : 'Name' }}</th>
                                    <th class="text-end">Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($report['rows'] as $row)
                                    <tr>
                                        <td>{{ $row->{$report['name']} }}</td>
                                        <td class="text-end fw-semibold">{{ \App\Support\TimeDisplay::formatHours($row->hours) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2">
                                            <div class="table-empty">No data for this filter.</div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @include('reports._export-modal')
</x-app-layout>
