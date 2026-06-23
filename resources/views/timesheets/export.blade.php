<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @page {
            size: a4 landscape;
            margin: 20px 24px 24px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #17324d;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10px;
            line-height: 1.4;
            background: #eef4fb;
        }

        .sheet {
            background: #fff;
            border: 1px solid #d8e2ee;
            border-radius: 18px;
            overflow: hidden;
        }

        .hero {
            padding: 18px 22px 16px;
            background: linear-gradient(180deg, rgba(239, 245, 252, 1) 0%, rgba(248, 251, 255, .98) 100%);
            border-bottom: 1px solid #d8e2ee;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 210px minmax(0, 1fr);
            gap: 18px 22px;
            align-items: start;
        }

        .brand-stack {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .logo-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 7px;
            min-height: 96px;
            padding: 12px 14px;
            border: 2px solid #2d78c4;
            border-radius: 4px;
            background: #fff;
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
            width: 92px;
            height: 46px;
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
            width: 52px;
            height: 52px;
            border-radius: 15px;
            color: #fff;
            background: linear-gradient(135deg, #1f4f7d 0%, #2f79c6 100%);
            font-size: 16px;
            font-weight: 800;
            letter-spacing: .04em;
        }

        .logo-copy {
            color: #17324d;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .meta-card {
            padding: 14px 14px 12px;
            border: 2px solid #2d78c4;
            border-radius: 4px;
            background: #fff;
        }

        .meta-row + .meta-row {
            margin-top: 9px;
            padding-top: 9px;
            border-top: 1px solid #e4edf7;
        }

        .meta-label {
            margin-bottom: 2px;
            color: #5f7184;
            font-size: 8px;
            font-weight: 700;
            letter-spacing: .11em;
            text-transform: uppercase;
        }

        .meta-value {
            color: #17324d;
            font-size: 11px;
            font-weight: 700;
        }

        .title-panel {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            justify-content: flex-start;
            min-height: 110px;
            padding-top: 4px;
            text-align: right;
        }

        .report-kicker {
            margin: 0 0 8px;
            color: #5f7184;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: .14em;
            text-transform: uppercase;
        }

        .title {
            margin: 0;
            color: #17324d;
            font-size: 24px;
            line-height: 1.06;
            font-weight: 800;
        }

        .subtitle {
            margin: 9px 0 0;
            max-width: 520px;
            color: #4f6479;
            font-size: 10px;
        }

        .content {
            padding: 14px 22px 20px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 12px;
        }

        .summary-card {
            padding: 11px 13px;
            border: 1px solid #d8e2ee;
            border-radius: 12px;
            background: #f8fbff;
        }

        .summary-label {
            color: #5f7184;
            font-size: 8px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .summary-value {
            margin-top: 3px;
            color: #17324d;
            font-size: 11px;
            font-weight: 700;
        }

        .total-block {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 12px;
            padding: 13px 15px;
            border: 1px solid #c8dcf5;
            border-radius: 14px;
            background: linear-gradient(135deg, #ecf4ff 0%, #f8fbff 100%);
        }

        .total-label {
            color: #5f7184;
            font-size: 8px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .total-value {
            margin-top: 2px;
            color: #17324d;
            font-size: 21px;
            font-weight: 800;
        }

        .total-note {
            max-width: 350px;
            color: #4f6479;
            font-size: 9px;
            text-align: right;
        }

        .table-wrap {
            border: 1px solid #d8e2ee;
            border-radius: 14px;
            overflow: hidden;
            background: #fff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        thead th {
            padding: 10px 11px;
            color: #fff;
            font-size: 8px;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            text-align: left;
            background: #204e7d;
        }

        tbody td {
            padding: 9px 11px;
            border-top: 1px solid #e3ebf4;
            vertical-align: top;
            word-break: break-word;
        }

        tbody tr:nth-child(even) td {
            background: #f9fbfe;
        }

        tbody tr {
            page-break-inside: avoid;
        }

        .text-end {
            text-align: right;
        }

        .fw-semibold {
            font-weight: 700;
        }

        .empty-row td {
            padding: 24px 11px;
            color: #5f7184;
            text-align: center;
        }

        .notes-cell {
            font-size: 9px;
        }

        .footer {
            margin-top: 10px;
            color: #6a7f93;
            font-size: 8px;
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
                                        <linearGradient id="scalyn-mark-gradient-timesheet" x1="0%" y1="0%" x2="100%" y2="100%">
                                            <stop offset="0%" stop-color="#1f4f7d" />
                                            <stop offset="100%" stop-color="#2f79c6" />
                                        </linearGradient>
                                    </defs>
                                    <path
                                        d="M16 60C16 36 34 20 60 20C86 20 108 36 120 48C132 36 154 20 180 20C206 20 224 36 224 60C224 84 206 100 180 100C154 100 132 84 120 72C108 84 86 100 60 100C34 100 16 84 16 60Z"
                                        fill="none"
                                        stroke="url(#scalyn-mark-gradient-timesheet)"
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
                            <div class="meta-value">{{ ucfirst($view) }}</div>
                        </div>
                        <div class="meta-row">
                            <div class="meta-label">Date range</div>
                            <div class="meta-value">{{ $from->format('M j, Y') }} to {{ $to->format('M j, Y') }}</div>
                        </div>
                    </div>
                </div>

                <div class="title-panel">
                    <div class="report-kicker">Export</div>
                    <h1 class="title">Timesheets</h1>
                    <p class="subtitle">A printable log of the selected time entries using the current filters and sort order.</p>
                </div>
            </div>
        </div>

        <div class="content">
            <div class="summary-grid">
                <div class="summary-card">
                    <div class="summary-label">View</div>
                    <div class="summary-value">{{ ucfirst($view) }}</div>
                </div>
                <div class="summary-card">
                    <div class="summary-label">Date range</div>
                    <div class="summary-value">{{ $from->format('M j, Y') }} to {{ $to->format('M j, Y') }}</div>
                </div>
                <div class="summary-card">
                    <div class="summary-label">Sort</div>
                    <div class="summary-value">{{ ucfirst($sort) }} {{ strtoupper($direction) }}</div>
                </div>
                <div class="summary-card">
                    <div class="summary-label">Entries</div>
                    <div class="summary-value">{{ $entries->count() }} {{ $entries->count() === 1 ? 'entry' : 'entries' }}</div>
                </div>
            </div>

            <div class="total-block">
                <div>
                    <div class="total-label">Total logged time</div>
                    <div class="total-value">{{ \App\Support\TimeDisplay::formatHours($totalHours) }}</div>
                </div>
                <div class="total-note">
                    Exported from the same filtered dataset shown on the Timesheets page.
                </div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 10%;">Date</th>
                            <th style="width: 14%;">User</th>
                            <th style="width: 14%;">Client</th>
                            <th style="width: 18%;">Task</th>
                            <th style="width: 30%;">Notes</th>
                            <th class="text-end" style="width: 14%;">Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($entries as $entry)
                            <tr>
                                <td class="fw-semibold">{{ $entry->date->format('M d, Y') }}</td>
                                <td>{{ $entry->user->name }}</td>
                                <td>{{ $entry->task->client->name }}</td>
                                <td class="fw-semibold">{{ $entry->task->title }}</td>
                                <td class="notes-cell">{{ $entry->notes ?: '—' }}</td>
                                <td class="text-end fw-semibold">{{ \App\Support\TimeDisplay::formatHours($entry->hours) }}</td>
                            </tr>
                        @empty
                            <tr class="empty-row">
                                <td colspan="6">No entries matched the selected filters.</td>
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
