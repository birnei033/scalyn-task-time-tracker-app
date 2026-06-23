<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Client;
use App\Models\TimeEntry;
use App\Models\User;
use App\Support\TimeDisplay;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->canManageTeam(), 403);

        return view('reports.index', $this->reportData($request));
    }

    public function export(Request $request)
    {
        abort_unless($request->user()->canManageTeam(), 403);

        $report = $this->reportDefinition($request->query('report'));
        $data = $this->reportData($request);

        return match (strtolower((string) $request->query('format', 'csv'))) {
            'pdf' => $this->exportPdf($report, $data),
            default => $this->exportCsv($report, $data),
        };
    }

    private function exportCsv(array $report, array $data)
    {
        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, $report['columns']);

        foreach ($data[$report['section']] as $row) {
            $csvRow = [$row->{$report['field']}, TimeDisplay::formatHours($row->hours)];

            if ($report['section'] === 'clientHours') {
                $csvRow[] = $row->budget_per_month === null
                    ? 'No monthly budget set.'
                    : TimeDisplay::formatMinutes(max(0, TimeDisplay::hoursToMinutes($row->hours) - (int) $row->budget_per_month));
            }

            fputcsv($handle, $csvRow);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$report['csvFilename'].'"',
        ]);
    }

    private function exportPdf(array $report, array $data)
    {
        $pdf = Pdf::loadView('reports.export', [
            'report' => $report,
            'data' => $data,
            'generatedAt' => now()->format('M j, Y g:i A'),
            'filters' => [
                ['label' => 'View', 'value' => ucfirst($data['view'])],
                ['label' => 'Date range', 'value' => Carbon::parse($data['from'])->format('M j, Y').' to '.Carbon::parse($data['to'])->format('M j, Y')],
                ['label' => 'Client', 'value' => $data['selectedClientName'] ?? 'All clients'],
                ['label' => 'User', 'value' => $data['selectedUserName'] ?? 'All users'],
            ],
        ])->setPaper('a4', 'portrait');

        return $pdf->download($report['pdfFilename']);
    }

    private function reportData(Request $request): array
    {
        $view = $request->query('view', 'monthly');
        $from = $request->query('from', now()->startOfMonth()->toDateString());
        $to = $request->query('to', now()->endOfMonth()->toDateString());
        $clientId = $request->filled('client_id') ? (int) $request->input('client_id') : null;
        $userId = $request->filled('user_id') ? (int) $request->input('user_id') : null;

        $base = TimeEntry::query()
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->when($clientId, function ($query, $clientId) {
                $query->whereHas('task', fn ($query) => $query->where('client_id', $clientId));
            })
            ->when($userId, fn ($query, $userId) => $query->where('user_id', $userId));

        $clients = Client::orderBy('name')->get();
        $users = User::orderBy('name')->get();

        return [
            'view' => $view,
            'from' => $from,
            'to' => $to,
            'clients' => $clients,
            'users' => $users,
            'selectedClientName' => $clientId ? $clients->firstWhere('id', $clientId)?->name : null,
            'selectedUserName' => $userId ? $users->firstWhere('id', $userId)?->name : null,
            'totalHours' => (clone $base)->sum('hours'),
            'clientHours' => (clone $base)
                ->join('tasks', 'time_entries.task_id', '=', 'tasks.id')
                ->join('clients', 'tasks.client_id', '=', 'clients.id')
                ->select('clients.name', 'clients.budget_per_month', DB::raw('sum(time_entries.hours) as hours'))
                ->groupBy('clients.id', 'clients.name', 'clients.budget_per_month')
                ->orderByDesc('hours')
                ->get(),
            'taskHours' => (clone $base)
                ->join('tasks', 'time_entries.task_id', '=', 'tasks.id')
                ->select('tasks.title', DB::raw('sum(time_entries.hours) as hours'))
                ->groupBy('tasks.id', 'tasks.title')
                ->orderByDesc('hours')
                ->get(),
            'userHours' => (clone $base)
                ->join('users', 'time_entries.user_id', '=', 'users.id')
                ->select('users.name', DB::raw('sum(time_entries.hours) as hours'))
                ->groupBy('users.id', 'users.name')
                ->orderByDesc('hours')
                ->get(),
        ];
    }

    private function reportDefinition(?string $report): array
    {
        return match ($report) {
            'clientHours' => [
                'section' => 'clientHours',
                'field' => 'name',
                'title' => 'Time per Client',
                'columns' => ['Name', 'Time', 'Excess Time'],
                'csvFilename' => 'scalyn-hours-per-client.csv',
                'pdfFilename' => 'scalyn-hours-per-client.pdf',
            ],
            'taskHours' => [
                'section' => 'taskHours',
                'field' => 'title',
                'title' => 'Time per Task',
                'columns' => ['Task', 'Time'],
                'csvFilename' => 'scalyn-hours-per-task.csv',
                'pdfFilename' => 'scalyn-hours-per-task.pdf',
            ],
            'userHours' => [
                'section' => 'userHours',
                'field' => 'name',
                'title' => 'Time per Employee',
                'columns' => ['Name', 'Time'],
                'csvFilename' => 'scalyn-hours-per-employee.csv',
                'pdfFilename' => 'scalyn-hours-per-employee.pdf',
            ],
            default => abort(404),
        };
    }
}
