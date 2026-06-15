<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use App\Support\RichText;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TimeEntryController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', TimeEntry::class);

        $user = request()->user();
        $entries = TimeEntry::with(['user', 'task.client'])
            ->when(! $user->canManageTeam(), fn ($query) => $query->where('user_id', $user->id))
            ->when(request('from'), fn ($query, $from) => $query->whereDate('date', '>=', $from))
            ->when(request('to'), fn ($query, $to) => $query->whereDate('date', '<=', $to))
            ->latest('date')
            ->paginate(15)
            ->withQueryString();

        return view('time-entries.index', array_merge(compact('entries'), $this->formData(new TimeEntry)));
    }

    public function create()
    {
        Gate::authorize('create', TimeEntry::class);

        return view('time-entries.create', $this->formData(new TimeEntry));
    }

    public function store(Request $request)
    {
        Gate::authorize('create', TimeEntry::class);

        $data = $this->validateEntry($request);
        $data['user_id'] = $request->user()->canManageTeam() ? $data['user_id'] : $request->user()->id;
        $data['notes'] = RichText::clean($data['notes'] ?? null);

        TimeEntry::create($data);

        return redirect()->route('time-entries.index')->with('status', 'Time entry saved.');
    }

    public function show(TimeEntry $timeEntry)
    {
        Gate::authorize('view', $timeEntry);

        return redirect()->route('time-entries.edit', $timeEntry);
    }

    public function edit(TimeEntry $timeEntry)
    {
        Gate::authorize('update', $timeEntry);

        return view('time-entries.edit', $this->formData($timeEntry));
    }

    public function update(Request $request, TimeEntry $timeEntry)
    {
        Gate::authorize('update', $timeEntry);

        $data = $this->validateEntry($request);
        $data['user_id'] = $request->user()->canManageTeam() ? $data['user_id'] : $timeEntry->user_id;
        $data['notes'] = RichText::clean($data['notes'] ?? null);
        $timeEntry->update($data);

        return redirect()->route('time-entries.index')->with('status', 'Time entry updated.');
    }

    public function destroy(TimeEntry $timeEntry)
    {
        Gate::authorize('delete', $timeEntry);

        $timeEntry->delete();

        return redirect()->route('time-entries.index')->with('status', 'Time entry deleted.');
    }

    private function formData(TimeEntry $entry): array
    {
        $user = request()->user();

        return [
            'entry' => $entry,
            'tasks' => Task::with('client')
                ->when(! $user->canManageTeam(), fn ($query) => $query->where('assigned_user_id', $user->id))
                ->orderBy('title')
                ->get(),
            'users' => User::orderBy('name')->get(),
        ];
    }

    private function validateEntry(Request $request): array
    {
        return $request->validate([
            'user_id' => [$request->user()->canManageTeam() ? 'required' : 'nullable', 'exists:users,id'],
            'task_id' => ['required', 'exists:tasks,id'],
            'date' => ['required', 'date'],
            'hours' => ['required', 'numeric', 'min:0.25', 'max:24'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
