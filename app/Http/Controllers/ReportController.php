<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\TimeEntry;
use App\Models\User;
use App\Support\TimeDisplay;
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

        $report = $this->reportExportDefinition($request->query('report'));
        $data = $this->reportData($request);
        $filename = $report['filename'];
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
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function reportData(Request $request): array
    {
        $view = $request->query('view', 'monthly');
        $from = $request->query('from', now()->startOfMonth()->toDateString());
        $to = $request->query('to', now()->endOfMonth()->toDateString());

        $base = TimeEntry::query()
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->when($request->integer('client_id'), function ($query, $clientId) {
                $query->whereHas('task', fn ($query) => $query->where('client_id', $clientId));
            })
            ->when($request->integer('user_id'), fn ($query, $userId) => $query->where('user_id', $userId));

        return [
            'view' => $view,
            'from' => $from,
            'to' => $to,
            'clients' => Client::orderBy('name')->get(),
            'users' => User::orderBy('name')->get(),
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

    private function reportExportDefinition(?string $report): array
    {
        return match ($report) {
            'clientHours' => [
                'section' => 'clientHours',
                'field' => 'name',
                'columns' => ['Name', 'Time', 'Excess Time'],
                'filename' => 'scalyn-hours-per-client.csv',
            ],
            'taskHours' => [
                'section' => 'taskHours',
                'field' => 'title',
                'columns' => ['Task', 'Time'],
                'filename' => 'scalyn-hours-per-task.csv',
            ],
            'userHours' => [
                'section' => 'userHours',
                'field' => 'name',
                'columns' => ['Name', 'Time'],
                'filename' => 'scalyn-hours-per-employee.csv',
            ],
            default => abort(404),
        };
    }
}
