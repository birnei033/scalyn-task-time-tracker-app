@php
    $rows = $data[$report['section']];
    $isClientReport = $report['section'] === 'clientHours';
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @page {
            size: a4 portrait;
            margin: 22px 24px 26px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #17324d;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            line-height: 1.45;
            background: #eef4fb;
        }

        .sheet {
            background: #ffffff;
            border: 1px solid #d8e2ee;
            border-radius: 20px;
            overflow: hidden;
        }

        .hero {
            padding: 20px 24px 18px;
            background: linear-gradient(180deg, rgba(239, 245, 252, 1) 0%, rgba(248, 251, 255, .98) 100%);
            border-bottom: 1px solid #d8e2ee;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 225px minmax(0, 1fr);
            gap: 18px 24px;
            align-items: start;
        }

        .brand-stack {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .logo-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 7px;
            min-height: 104px;
            padding: 14px 16px;
            border: 2px solid #2d78c4;
            border-radius: 4px;
            background: #ffffff;
            text-align: center;
        }

        .logo-assembly {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
        }

        .logo-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 104px;
            height: 52px;
            flex: 0 0 auto;
        }

        .logo-icon svg {
            width: 100%;
            height: 100%;
            display: block;
        }

        .logo-mark {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 58px;
            height: 58px;
            border-radius: 16px;
            color: #ffffff;
            background: linear-gradient(135deg, #1f4f7d 0%, #2f79c6 100%);
            font-size: 17px;
            font-weight: 800;
            letter-spacing: .04em;
        }

        .logo-copy {
            color: #17324d;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .meta-card {
            padding: 16px 16px 14px;
            border: 2px solid #2d78c4;
            border-radius: 4px;
            background: #ffffff;
        }

        .meta-label {
            margin-bottom: 2px;
            color: #5f7184;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: .11em;
            text-transform: uppercase;
        }

        .meta-value {
            color: #17324d;
            font-size: 13px;
            font-weight: 700;
        }

        .meta-row + .meta-row {
            margin-top: 11px;
            padding-top: 11px;
            border-top: 1px solid #e4edf7;
        }

        .title-panel {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            justify-content: flex-start;
            min-height: 118px;
            padding-top: 6px;
            text-align: right;
        }

        .report-kicker {
            margin: 0 0 8px;
            color: #5f7184;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .14em;
            text-transform: uppercase;
        }

        .title {
            margin: 0;
            max-width: 100%;
            color: #17324d;
            font-size: 26px;
            line-height: 1.06;
            font-weight: 800;
        }

        .content {
            padding: 10px 24px 22px;
        }

        .total-block {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            margin-bottom: 14px;
            padding: 14px 16px;
            border-radius: 16px;
            color: #17324d;
            background: linear-gradient(135deg, #ecf4ff 0%, #f8fbff 100%);
            border: 1px solid #c8dcf5;
        }

        .total-label {
            color: #5f7184;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .total-value {
            margin-top: 2px;
            font-size: 24px;
            line-height: 1.1;
            font-weight: 800;
            color: #17324d;
        }

        .total-note {
            max-width: 420px;
            color: #4f6479;
            font-size: 10px;
            text-align: right;
        }

        .table-wrap {
            border: 1px solid #d8e2ee;
            border-radius: 16px;
            overflow: hidden;
            background: #ffffff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            padding: 12px 14px;
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            text-align: left;
            background: #204e7d;
        }

        tbody td {
            padding: 11px 14px;
            border-top: 1px solid #e3ebf4;
            vertical-align: top;
        }

        tbody tr:nth-child(even) td {
            background: #f9fbfe;
        }

        .text-end {
            text-align: right;
        }

        .fw-semibold {
            font-weight: 700;
        }

        .empty-row td {
            padding: 28px 14px;
            color: #5f7184;
            text-align: center;
            background: #ffffff;
        }

        .budget-pill {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 999px;
            background: #e9f2ff;
            color: #18406b;
            font-weight: 700;
            white-space: nowrap;
        }

        .footer {
            margin-top: 12px;
            color: #6a7f93;
            font-size: 9px;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="hero">
            <div class="hero-grid">
                <div class="brand-stack">
                    <div class="logo-card">
                        <div class="logo-assembly">
                            <div class="logo-icon" aria-hidden="true">
                                <svg viewBox="0 0 240 120" role="img" aria-label="Scalyn mark">
                                    <defs>
                                        <linearGradient id="scalyn-mark-gradient-report" x1="0%" y1="0%" x2="100%" y2="100%">
                                            <stop offset="0%" stop-color="#1f4f7d" />
                                            <stop offset="100%" stop-color="#2f79c6" />
                                        </linearGradient>
                                    </defs>
                                    <path
                                        d="M16 60C16 36 34 20 60 20C86 20 108 36 120 48C132 36 154 20 180 20C206 20 224 36 224 60C224 84 206 100 180 100C154 100 132 84 120 72C108 84 86 100 60 100C34 100 16 84 16 60Z"
                                        fill="none"
                                        stroke="url(#scalyn-mark-gradient-report)"
                                        stroke-width="18"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                            </div>
                            <div class="logo-mark">ST</div>
                        </div>
                        <div class="logo-copy">Scalyn Task Time Tracker</div>
                    </div>

                    <div class="meta-card">
                        <div class="meta-row">
                            <div class="meta-label">Generated</div>
                            <div class="meta-value">{{ $generatedAt }}</div>
                        </div>
                        <div class="meta-row">
                            <div class="meta-label">View</div>
                            <div class="meta-value">{{ ucfirst($data['view']) }}</div>
                        </div>
                        <div class="meta-row">
                            <div class="meta-label">Date range</div>
                            <div class="meta-value">{{ \Illuminate\Support\Carbon::parse($data['from'])->format('M j, Y') }} to {{ \Illuminate\Support\Carbon::parse($data['to'])->format('M j, Y') }}</div>
                        </div>
                    </div>
                </div>

                <div class="title-panel">
                    <div class="report-kicker">Report</div>
                    <h1 class="title">{{ $report['title'] }}</h1>
                </div>
            </div>
        </div>

        <div class="content">
            <div class="total-block">
                <div>
                    <div class="total-label">Total logged time</div>
                    <div class="total-value">{{ \App\Support\TimeDisplay::formatHours($data['totalHours']) }}</div>
                </div>
                <div class="total-note">
                    {{ count($rows) }} {{ count($rows) === 1 ? 'row' : 'rows' }} in this breakdown. Values are grouped from the currently selected report filters.
                </div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>{{ $isClientReport ? 'Client' : ($report['section'] === 'taskHours' ? 'Task' : 'Employee') }}</th>
                            <th class="text-end">Time</th>
                            @if ($isClientReport)
                                <th class="text-end">Excess time</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td class="fw-semibold">{{ $row->{$report['field']} }}</td>
                                <td class="text-end fw-semibold">{{ \App\Support\TimeDisplay::formatHours($row->hours) }}</td>
                                @if ($isClientReport)
                                    <td class="text-end">
                                        <span class="budget-pill">
                                            {{ $row->budget_per_month === null
                                                ? 'No monthly budget set.'
                                                : \App\Support\TimeDisplay::formatMinutes(max(0, \App\Support\TimeDisplay::hoursToMinutes($row->hours) - (int) $row->budget_per_month)) }}
                                        </span>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr class="empty-row">
                                <td colspan="{{ $isClientReport ? 3 : 2 }}">No data matched the selected filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="footer">
                Export generated by Scalyn Task Time Tracker
            </div>
        </div>
    </div>
</body>
</html>
