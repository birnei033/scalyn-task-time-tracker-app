<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\TaskActivityEntry;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\User;
use App\Support\RichText;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Task::class);

        $user = $request->user();
        $statusTask = null;
        $logTimeTask = null;

        if (session()->getOldInput('modal_form') === 'task-status-modal' && session()->getOldInput('task_id')) {
            $statusTask = Task::with('client')->find(session()->getOldInput('task_id'));
        }

        if (session()->getOldInput('modal_form') === 'task-log-time-modal' && session()->getOldInput('task_id')) {
            $logTimeTask = Task::with('client')->find(session()->getOldInput('task_id'));
        }

        $assignedUserId = $request->integer('assigned_user_id');

        $tasks = Task::with($this->detailRelations())
            ->when(! $user->canManageTeam(), fn ($query) => $query->where('assigned_user_id', $user->id))
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhereHas('client', fn ($clientQuery) => $clientQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('assignedUser', fn ($assignedUserQuery) => $assignedUserQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->input('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($assignedUserId, fn ($query) => $query->where('assigned_user_id', $assignedUserId))
            ->when($request->integer('client_id'), fn ($query, $clientId) => $query->where('client_id', $clientId))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('tasks.index', array_merge(compact('tasks', 'statusTask', 'logTimeTask'), $this->formData(new Task)));
    }

    public function create()
    {
        Gate::authorize('create', Task::class);

        return view('tasks.create', $this->formData(new Task));
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Task::class);

        $validated = $this->validateTask($request);

        DB::transaction(function () use ($validated, $request) {
            $task = Task::create($this->buildTaskData($validated));
            $this->recordTaskActivity($task, $request->user(), 'created');
            $this->syncAttachments($task, $request);
        });

        return redirect()->route('tasks.index')->with('status', 'Task created.');
    }

    public function show(Task $task)
    {
        Gate::authorize('view', $task);

        return view('tasks.show', $this->formData($task));
    }

    public function edit(Task $task)
    {
        Gate::authorize('update', $task);

        return view('tasks.edit', $this->formData($task));
    }

    public function update(Request $request, Task $task)
    {
        Gate::authorize('update', $task);

        $validated = Validator::make($request->all(), $this->updateRules($request))->validate();
        $original = $task->only(['client_id', 'assigned_user_id', 'title', 'description', 'status', 'priority']);

        $taskData = [
            'status' => $validated['status'],
        ];

        if ($request->user()->canManageTeam()) {
            $taskData = array_merge($taskData, $this->buildTaskData($validated));
        }

        $hasProgressUpdate = filled($validated['progress_notes'] ?? null);

        DB::transaction(function () use ($task, $taskData, $validated, $request, $hasProgressUpdate, $original) {
            $task->update($taskData);
            $this->recordTaskChanges($task, $request->user(), $original, $taskData);

            if ($hasProgressUpdate) {
                $task->progressEntries()->create([
                    'user_id' => $request->user()->id,
                    'date' => now()->toDateString(),
                    'notes' => RichText::clean($validated['progress_notes']),
                ]);
            }

            $this->syncAttachments($task, $request);
        });

        return redirect()->route('tasks.index')->with('status', 'Task updated.');
    }

    public function updateStatus(Request $request, Task $task)
    {
        Gate::authorize('update', $task);

        $validated = $request->validate($this->statusRules());
        $original = $task->only(['status']);

        DB::transaction(function () use ($task, $validated, $request, $original) {
            $task->update([
                'status' => $validated['status'],
            ]);

            $this->recordTaskChanges($task, $request->user(), $original, [
                'status' => $validated['status'],
            ]);
        });

        return redirect()->route('tasks.index')->with('status', 'Task status updated.');
    }

    public function logTime(Request $request)
    {
        $validated = Validator::make($request->all(), $this->logTimeRules($request))->validate();
        $task = Task::with('client')->findOrFail($validated['task_id']);

        Gate::authorize('update', $task);

        $original = $task->only(['status']);
        $userId = $request->user()->canManageTeam()
            ? $validated['user_id']
            : $request->user()->id;

        DB::transaction(function () use ($task, $validated, $request, $userId, $original) {
            $task->update([
                'status' => $validated['status'],
            ]);

            $this->recordTaskChanges($task, $request->user(), $original, [
                'status' => $validated['status'],
            ]);

            $task->timeEntries()->create([
                'user_id' => $userId,
                'date' => $validated['date'],
                'hours' => $validated['hours'],
                'notes' => RichText::clean($validated['notes'] ?? null),
            ]);
        });

        return redirect()->to($this->logTimeReturnUrl($request, $task))->with('status', 'Time entry saved.');
    }

    public function destroy(Task $task)
    {
        Gate::authorize('delete', $task);

        $task->delete();

        return redirect()->route('tasks.index')->with('status', 'Task deleted.');
    }

    private function formData(Task $task): array
    {
        if ($task->exists) {
            $task->loadMissing($this->detailRelations());
        }

        return [
            'task' => $task,
            'clients' => $this->taskClients($task),
            'users' => User::orderBy('name')->get(),
            'hasTaskActivityTable' => $this->hasTaskActivityTable(),
        ];
    }

    private function detailRelations(): array
    {
        $relations = ['client', 'assignedUser', 'attachments.uploader', 'timeEntries.user', 'progressEntries.user', 'comments.user'];

        if ($this->hasTaskActivityTable()) {
            $relations[] = 'activityEntries.user';
        }

        return $relations;
    }

    private function validateTask(Request $request): array
    {
        return $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'assigned_user_id' => ['nullable', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:'.implode(',', Task::statusValues())],
            'priority' => ['required', 'in:low,medium,high'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => [
                'file',
                'max:20480',
                'mimetypes:image/jpeg,image/png,image/gif,image/webp,image/bmp,image/tiff,application/pdf,text/plain,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,application/rtf,application/vnd.oasis.opendocument.text,application/vnd.oasis.opendocument.spreadsheet,application/vnd.oasis.opendocument.presentation',
            ],
            'progress_notes' => ['nullable', 'string'],
        ]);
    }

    private function logTimeRules(Request $request): array
    {
        $canManageTeam = $request->user()->canManageTeam();

        return [
            'task_id' => ['required', 'exists:tasks,id'],
            'return_to' => ['nullable', 'string'],
            'status' => ['required', 'in:'.implode(',', Task::statusValues())],
            'date' => ['required', 'date'],
            'hours' => ['required', 'numeric', 'min:0.25', 'max:24'],
            'notes' => ['nullable', 'string'],
            'user_id' => [$canManageTeam ? 'required' : 'nullable', 'exists:users,id'],
        ];
    }

    private function statusRules(): array
    {
        return [
            'status' => ['required', 'in:'.implode(',', Task::statusValues())],
            'modal_form' => ['nullable', 'string'],
            'task_id' => ['nullable', 'exists:tasks,id'],
        ];
    }

    private function buildTaskData(array $validated): array
    {
        return [
            'client_id' => $validated['client_id'],
            'assigned_user_id' => $validated['assigned_user_id'] ?? null,
            'title' => $validated['title'],
            'description' => RichText::clean($validated['description'] ?? null),
            'status' => $validated['status'],
            'priority' => $validated['priority'],
        ];
    }

    private function syncAttachments(Task $task, Request $request): void
    {
        if (! $request->hasFile('attachments')) {
            return;
        }

        foreach ($request->file('attachments', []) as $file) {
            if (! $file || ! $file->isValid()) {
                continue;
            }

            $originalName = $file->getClientOriginalName();
            $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin');
            $storedName = Str::uuid()->toString().'.'.$extension;
            $directory = 'task-attachments/'.$task->id;
            $path = Storage::disk('local')->putFileAs($directory, $file, $storedName);

            if (! $path) {
                continue;
            }

            TaskAttachment::create([
                'task_id' => $task->id,
                'user_id' => $request->user()->id,
                'path' => $path,
                'original_name' => $originalName,
                'mime_type' => $file->getClientMimeType() ?: $file->getMimeType(),
                'size' => $file->getSize() ?: 0,
            ]);
        }
    }

    private function recordTaskActivity(Task $task, ?User $user, string $action): void
    {
        if (! $this->hasTaskActivityTable()) {
            return;
        }

        TaskActivityEntry::create([
            'task_id' => $task->id,
            'user_id' => $user?->id,
            'action' => $action,
        ]);
    }

    private function recordTaskChanges(Task $task, ?User $user, array $original, array $applied): void
    {
        if (! $this->hasTaskActivityTable()) {
            return;
        }

        foreach (['client_id', 'assigned_user_id', 'title', 'description', 'status', 'priority'] as $field) {
            if (! array_key_exists($field, $applied)) {
                continue;
            }

            $oldValue = $original[$field] ?? null;
            $newValue = $applied[$field];

            if ((string) $oldValue === (string) $newValue) {
                continue;
            }

            TaskActivityEntry::create([
                'task_id' => $task->id,
                'user_id' => $user?->id,
                'action' => 'updated',
                'field' => $field,
                'old_value' => $this->historyValue($field, $oldValue),
                'new_value' => $this->historyValue($field, $newValue),
            ]);
        }
    }

    private function historyValue(string $field, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return match ($field) {
            'client_id' => Client::find($value)?->name,
            'assigned_user_id' => User::find($value)?->name,
            'status', 'priority' => Str::headline(str_replace('_', ' ', (string) $value)),
            'description' => RichText::excerpt((string) $value),
            default => (string) $value,
        };
    }

    private function hasTaskActivityTable(): bool
    {
        return Schema::hasTable('task_activity_entries');
    }

    private function taskClients(Task $task)
    {
        $clients = Client::active()->orderBy('name')->get();

        if (! $task->exists || ! $task->client) {
            return $clients;
        }

        if ($clients->contains('id', $task->client_id)) {
            return $clients;
        }

        return $clients->push($task->client)->sortBy('name')->values();
    }

    private function logTimeReturnUrl(Request $request, Task $task): string
    {
        foreach ([$request->input('return_to'), $request->headers->get('referer')] as $candidate) {
            if ($this->isSafeInternalUrl($candidate)) {
                return $candidate;
            }
        }

        return route('tasks.show', $task).'#logged-time-pane';
    }

    private function isSafeInternalUrl(?string $candidate): bool
    {
        if (! is_string($candidate) || $candidate === '') {
            return false;
        }

        $parts = parse_url($candidate);

        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return false;
        }

        return $parts['scheme'] === request()->getScheme()
            && $parts['host'] === request()->getHost();
    }

    private function updateRules(Request $request): array
    {
        $canManageTeam = $request->user()->canManageTeam();

        return [
            'client_id' => [$canManageTeam ? 'required' : 'nullable', 'exists:clients,id'],
            'assigned_user_id' => ['nullable', 'exists:users,id'],
            'title' => [$canManageTeam ? 'required' : 'nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:'.implode(',', Task::statusValues())],
            'priority' => [$canManageTeam ? 'required' : 'nullable', 'in:low,medium,high'],
            'user_id' => ['nullable', 'exists:users,id'],
            'date' => ['nullable', 'date'],
            'hours' => ['nullable', 'numeric', 'min:0.25', 'max:24'],
            'notes' => ['nullable', 'string'],
            'progress_notes' => ['nullable', 'string'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => [
                'file',
                'max:20480',
                'mimetypes:image/jpeg,image/png,image/gif,image/webp,image/bmp,image/tiff,application/pdf,text/plain,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,application/rtf,application/vnd.oasis.opendocument.text,application/vnd.oasis.opendocument.spreadsheet,application/vnd.oasis.opendocument.presentation',
            ],
        ];
    }
}
