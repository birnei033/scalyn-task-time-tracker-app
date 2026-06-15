<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class TimesheetController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        [$entries, $view, $from, $to, $sort, $direction] = $this->timesheetPayload($request);

        return view('timesheets.index', [
            'entries' => $entries,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'view' => $view,
            'clients' => Client::orderBy('name')->get(),
            'users' => $user->canManageTeam() ? User::orderBy('name')->get() : collect([$user]),
            'totalHours' => $entries->sum('hours'),
            'dailyTotals' => $entries->groupBy(fn ($entry) => $entry->date->toDateString())->map->sum('hours'),
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function export(Request $request)
    {
        [$entries] = $this->timesheetPayload($request);
        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, ['Date', 'User', 'Client', 'Task', 'Notes', 'Hours']);

        foreach ($entries as $entry) {
            fputcsv($handle, [
                $entry->date->format('M d, Y'),
                $entry->user->name,
                $entry->task->client->name,
                $entry->task->title,
                $entry->notes,
                number_format((float) $entry->hours, 2, '.', ''),
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="scalyn-timesheets.csv"',
        ]);
    }

    /**
     * @return array{0:\Illuminate\Support\Collection<int, \App\Models\TimeEntry>, 1:string, 2:\Illuminate\Support\Carbon, 3:\Illuminate\Support\Carbon, 4:string, 5:string}
     */
    private function timesheetPayload(Request $request): array
    {
        $view = $request->query('view', 'weekly');
        $from = $this->resolveFromDate($request, $view);
        $to = $this->resolveToDate($request, $view, $from);
        [$sort, $direction] = $this->sortState($request->query('sort', 'date'), $request->query('direction', 'asc'));

        return [
            $this->timesheetQuery($request, $from, $to, $sort, $direction)->get(),
            $view,
            $from,
            $to,
            $sort,
            $direction,
        ];
    }

    private function timesheetQuery(Request $request, $from, $to, string $sort, string $direction): Builder
    {
        $user = $request->user();
        $clientId = $request->integer('client_id');

        return TimeEntry::query()
            ->select('time_entries.*')
            ->with(['user', 'task.client'])
            ->leftJoin('users', 'time_entries.user_id', '=', 'users.id')
            ->leftJoin('tasks', 'time_entries.task_id', '=', 'tasks.id')
            ->leftJoin('clients', 'tasks.client_id', '=', 'clients.id')
            ->whereDate('time_entries.date', '>=', $from->toDateString())
            ->whereDate('time_entries.date', '<=', $to->toDateString())
            ->when(! $user->canManageTeam(), fn ($query) => $query->where('time_entries.user_id', $user->id))
            ->when($user->canManageTeam() && $request->integer('user_id'), fn ($query) => $query->where('time_entries.user_id', $request->integer('user_id')))
            ->when($clientId, fn ($query) => $query->whereHas('task', fn ($query) => $query->where('client_id', $clientId)))
            ->orderBy($sort, $direction)
            ->orderBy('time_entries.id', $direction);
    }

    private function resolveFromDate(Request $request, string $view)
    {
        return match ($view) {
            'daily' => $request->date('from') ?: now()->startOfDay(),
            'monthly' => $request->date('from') ?: now()->startOfMonth(),
            default => $request->date('from') ?: now()->startOfWeek(),
        };
    }

    private function resolveToDate(Request $request, string $view, $from)
    {
        return match ($view) {
            'daily' => $from->copy()->endOfDay(),
            'monthly' => $request->date('to') ?: now()->endOfMonth(),
            default => $request->date('to') ?: now()->endOfWeek(),
        };
    }

    private function sortState(string $sort, string $direction): array
    {
        $columns = [
            'date' => 'time_entries.date',
            'user' => 'users.name',
            'client' => 'clients.name',
            'task' => 'tasks.title',
            'notes' => 'time_entries.notes',
            'hours' => 'time_entries.hours',
        ];

        if (! array_key_exists($sort, $columns)) {
            return [$columns['date'], 'asc'];
        }

        return [$columns[$sort], in_array(strtolower($direction), ['asc', 'desc'], true) ? strtolower($direction) : 'asc'];
    }
}
