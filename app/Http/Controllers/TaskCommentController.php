<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskComment;
use App\Support\RichText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class TaskCommentController extends Controller
{
    public function store(Request $request, Task $task)
    {
        Gate::authorize('view', $task);

        $validated = $this->validateComment($request, 'body');
        if ($validated instanceof \Illuminate\Http\RedirectResponse) {
            return $validated;
        }

        TaskComment::create([
            'task_id' => $task->id,
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        return redirect()->to($this->returnTo($request, $task))->with('status', 'Comment added.');
    }

    public function update(Request $request, Task $task, TaskComment $comment)
    {
        Gate::authorize('view', $task);
        $this->authorizeCommentOwner($request, $comment);

        $validated = $this->validateComment($request, 'edit_body');
        if ($validated instanceof \Illuminate\Http\RedirectResponse) {
            return $validated;
        }

        $comment->update([
            'body' => $validated['body'],
        ]);

        return redirect()->to($this->returnTo($request, $task))->with('status', 'Comment updated.');
    }

    public function destroy(Request $request, Task $task, TaskComment $comment)
    {
        Gate::authorize('view', $task);
        $this->authorizeCommentOwner($request, $comment);

        $comment->delete();

        return redirect()->to($this->returnTo($request, $task))->with('status', 'Comment deleted.');
    }

    private function validateComment(Request $request, string $fieldName): array|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            $fieldName => ['nullable', 'string'],
            'comment_id' => ['nullable', 'integer'],
            'return_to' => ['nullable', 'string'],
        ]);

        $validator->after(function ($validator) use ($request, $fieldName) {
            if (RichText::clean($request->input($fieldName)) === null) {
                $validator->errors()->add($fieldName, 'Comment body is required.');
            }
        });

        if ($validator->fails()) {
            return $this->redirectWithCommentErrors($request, $request->route('task'), $validator->errors()->messages());
        }

        return [
            'body' => RichText::clean($request->input($fieldName)),
        ];
    }

    private function redirectWithCommentErrors(Request $request, Task $task, array $messages)
    {
        return redirect()
            ->to($this->returnTo($request, $task))
            ->withErrors($messages)
            ->withInput();
    }

    private function authorizeCommentOwner(Request $request, TaskComment $comment): void
    {
        abort_unless($comment->user_id === $request->user()->id, 403);
    }

    private function returnTo(Request $request, Task $task): string
    {
        $candidate = $request->input('return_to');

        if ($this->isSafeInternalUrl($candidate)) {
            return $candidate;
        }

        return route('tasks.show', $task).'#comments-pane';
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
