<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskAttachment;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class TaskAttachmentController extends Controller
{
    public function show(Task $task, TaskAttachment $attachment)
    {
        $this->authorizeAttachment($task, $attachment);

        return Storage::disk('local')->response(
            $attachment->path,
            $attachment->original_name,
            ['Content-Type' => $attachment->mime_type ?: 'application/octet-stream'],
            'inline',
        );
    }

    public function download(Task $task, TaskAttachment $attachment)
    {
        $this->authorizeAttachment($task, $attachment);

        return Storage::disk('local')->download(
            $attachment->path,
            $attachment->original_name,
            ['Content-Type' => $attachment->mime_type ?: 'application/octet-stream'],
        );
    }

    private function authorizeAttachment(Task $task, TaskAttachment $attachment): void
    {
        Gate::authorize('view', $task);

        abort_unless($attachment->task_id === $task->id, 404);
    }
}
