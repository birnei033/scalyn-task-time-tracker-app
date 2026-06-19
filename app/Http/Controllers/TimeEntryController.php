<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use App\Support\RichText;
use App\Support\TimeDisplay;
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

        $editingEntryId = old('editing_entry', request('editing_entry'));
        $selectedEntry = $editingEntryId
            ? ($entries->firstWhere('id', (int) $editingEntryId) ?: TimeEntry::with(['user', 'task.client'])->find($editingEntryId))
            : null;

        return view('time-entries.index', array_merge(
            compact('entries', 'selectedEntry'),
            [
                'showEditModal' => old('modal_form') === 'time-entry-edit-modal' || request()->filled('editing_entry'),
            ],
            $this->formData(new TimeEntry)
        ));
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
        $data['hours'] = TimeDisplay::minutesToHours($data['minutes']);
        unset($data['minutes']);
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
        $data['hours'] = TimeDisplay::minutesToHours($data['minutes']);
        unset($data['minutes']);
        $data['user_id'] = $request->user()->canManageTeam() ? $data['user_id'] : $timeEntry->user_id;
        $data['notes'] = RichText::clean($data['notes'] ?? null);
        $timeEntry->update($data);

        $returnTo = $request->input('return_to');

        return $returnTo
            ? redirect()->to($returnTo)->with('status', 'Time entry updated.')
            : redirect()->route('time-entries.index')->with('status', 'Time entry updated.');
    }

    public function destroy(Request $request, TimeEntry $timeEntry)
    {
        Gate::authorize('delete', $timeEntry);

        $timeEntry->delete();

        $returnTo = $request->input('return_to');

        return $returnTo
            ? redirect()->to($returnTo)->with('status', 'Time entry deleted.')
            : redirect()->route('time-entries.index')->with('status', 'Time entry deleted.');
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
            'minutes' => $this->minutesRules(),
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function minutesRules(): array
    {
        return ['required', 'integer', 'min:1', 'max:480'];
    }
}
