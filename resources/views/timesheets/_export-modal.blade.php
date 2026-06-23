@php
    $selectedClient = request('client_id') ? $clients->firstWhere('id', (int) request('client_id')) : null;
    $selectedUser = request('user_id') ? $users->firstWhere('id', (int) request('user_id')) : null;
    $userLabel = auth()->user()->canManageTeam() ? ($selectedUser?->name ?? 'All users') : auth()->user()->name;
@endphp

<x-modal name="timesheet-export-modal" maxWidth="lg">
    <form method="GET" action="{{ route('timesheets.export') }}" id="timesheet-export-form">
        <input type="hidden" name="view" value="{{ $view }}">
        <input type="hidden" name="from" value="{{ $from }}">
        <input type="hidden" name="to" value="{{ $to }}">
        <input type="hidden" name="client_id" value="{{ request('client_id', '') }}">
        <input type="hidden" name="user_id" value="{{ request('user_id', '') }}">
        <input type="hidden" name="sort" value="{{ $sort }}">
        <input type="hidden" name="direction" value="{{ $direction }}">

        <div class="modal-header">
            <div>
                <div class="section-kicker mb-1">Export timesheet</div>
                <h2 class="modal-title fs-5 mb-0">Choose a format</h2>
            </div>
            <button type="button" class="btn-close" data-swal-close aria-label="Close"></button>
        </div>

        <div class="modal-body">
            <p class="muted-copy mb-3">
                Export the currently filtered and sorted timesheet entries without changing the table state.
            </p>

            <div class="report-export-summary mb-4">
                <div class="report-export-summary-item">
                    <span class="report-export-summary-label">View</span>
                    <span class="report-export-summary-value">{{ ucfirst($view) }}</span>
                </div>
                <div class="report-export-summary-item">
                    <span class="report-export-summary-label">Date range</span>
                    <span class="report-export-summary-value">{{ \Illuminate\Support\Carbon::parse($from)->format('M j, Y') }} to {{ \Illuminate\Support\Carbon::parse($to)->format('M j, Y') }}</span>
                </div>
                <div class="report-export-summary-item">
                    <span class="report-export-summary-label">Client</span>
                    <span class="report-export-summary-value">{{ $selectedClient?->name ?? 'All clients' }}</span>
                </div>
                <div class="report-export-summary-item">
                    <span class="report-export-summary-label">User</span>
                    <span class="report-export-summary-value">{{ $userLabel }}</span>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <button type="submit" name="format" value="csv" class="report-export-choice w-100" data-loading-text="Preparing CSV...">
                        <span class="report-export-choice-icon">
                            <i class="bi bi-filetype-csv"></i>
                        </span>
                        <span class="report-export-choice-copy">
                            <span class="report-export-choice-title">CSV</span>
                            <span class="report-export-choice-text">Best for spreadsheets, filtering, and quick analysis.</span>
                        </span>
                    </button>
                </div>
                <div class="col-md-6">
                    <button type="submit" name="format" value="pdf" class="report-export-choice report-export-choice-primary w-100" data-loading-text="Preparing PDF...">
                        <span class="report-export-choice-icon">
                            <i class="bi bi-file-earmark-pdf"></i>
                        </span>
                        <span class="report-export-choice-copy">
                            <span class="report-export-choice-title">PDF</span>
                            <span class="report-export-choice-text">Best for printing or sharing a polished timesheet summary.</span>
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</x-modal>
