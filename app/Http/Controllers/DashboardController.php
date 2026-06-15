<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $entryQuery = TimeEntry::query();

        if (! $user->canManageTeam()) {
            $entryQuery->where('user_id', $user->id);
        }

        return view('dashboard', [
            'totalHours' => (clone $entryQuery)->sum('hours'),
            'weekHours' => (clone $entryQuery)
                ->whereDate('date', '>=', now()->startOfWeek()->toDateString())
                ->whereDate('date', '<=', now()->endOfWeek()->toDateString())
                ->sum('hours'),
            'activeClients' => Client::active()->count(),
            'openTasks' => Task::query()
                ->when(! $user->canManageTeam(), fn ($query) => $query->where('assigned_user_id', $user->id))
                ->where('status', 'open')
                ->count(),
            'teamMembers' => $user->canManageTeam() ? User::count() : null,
            'recentEntries' => (clone $entryQuery)->with(['user', 'task.client'])->latest('date')->limit(6)->get(),
            'clientHours' => (clone $entryQuery)
                ->join('tasks', 'time_entries.task_id', '=', 'tasks.id')
                ->join('clients', 'tasks.client_id', '=', 'clients.id')
                ->select('clients.name', DB::raw('sum(time_entries.hours) as hours'))
                ->groupBy('clients.id', 'clients.name')
                ->orderByDesc('hours')
                ->limit(5)
                ->get(),
        ]);
    }
}
