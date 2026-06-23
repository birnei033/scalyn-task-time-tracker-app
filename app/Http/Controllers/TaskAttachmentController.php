<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskAttachmentVersion;
use App\Support\TaskAttachmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class TaskAttachmentController extends Controller
{
    public function __construct(private TaskAttachmentService $taskAttachmentService)
    {
    }

    public function show(Task $task, TaskAttachment $attachment)
    {
        $this->authorizeAttachmentView($task, $attachment);

        return Storage::disk('local')->response(
            $attachment->path,
            $attachment->original_name,
            ['Content-Type' => $attachment->mime_type ?: 'application/octet-stream'],
            'inline',
        );
    }

    public function download(Task $task, TaskAttachment $attachment)
    {
        $this->authorizeAttachmentView($task, $attachment);

        return Storage::disk('local')->download(
            $attachment->path,
            $attachment->original_name,
            ['Content-Type' => $attachment->mime_type ?: 'application/octet-stream'],
        );
    }

    public function destroy(Request $request, Task $task, TaskAttachment $attachment)
    {
        $this->authorizeAttachmentManagement($request, $task, $attachment);
        abort_if($attachment->trashed(), 404);

        $this->taskAttachmentService->softDelete($attachment, $request->user());

        return redirect()->to($this->safeReturnUrl($request, route('tasks.show', $task)))
            ->with('status', 'Attachment deleted.');
    }

    public function replace(Request $request, Task $task, TaskAttachment $attachment)
    {
        $this->authorizeAttachmentManagement($request, $task, $attachment);
        abort_if($attachment->trashed(), 404);

        $validated = $request->validate(TaskAttachmentService::singleFileRules());
        $this->taskAttachmentService->replace($attachment, $validated['attachment'], $request->user());

        return redirect()->to($this->safeReturnUrl($request, route('tasks.show', $task)))
            ->with('status', 'Attachment replaced.');
    }

    public function restore(Request $request, Task $task, TaskAttachment $attachment, TaskAttachmentVersion $version)
    {
        $this->authorizeAttachmentManagement($request, $task, $attachment);

        abort_unless($version->task_attachment_id === $attachment->id, 404);

        $this->taskAttachmentService->restoreVersion($attachment, $version, $request->user());

        return redirect()->to($this->safeReturnUrl($request, route('tasks.show', $task)))
            ->with('status', 'Attachment version restored.');
    }

    private function authorizeAttachmentView(Task $task, TaskAttachment $attachment): void
    {
        Gate::authorize('view', $task);

        abort_unless($attachment->task_id === $task->id, 404);
        abort_if($attachment->trashed(), 404);
    }

    private function authorizeAttachmentManagement(Request $request, Task $task, TaskAttachment $attachment): void
    {
        Gate::authorize('view', $task);

        abort_unless($request->user()->canManageTeam(), 403);
        abort_unless($attachment->task_id === $task->id, 404);
    }

    private function safeReturnUrl(Request $request, string $fallback): string
    {
        foreach ([$request->input('return_to'), $request->headers->get('referer')] as $candidate) {
            if ($this->isSafeInternalUrl($candidate)) {
                return $candidate;
            }
        }

        return $fallback;
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
}
