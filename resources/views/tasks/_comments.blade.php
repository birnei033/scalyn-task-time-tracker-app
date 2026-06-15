@php($commentReturnTo = route('tasks.show', $task).'#comments-pane')
@php($editingCommentId = old('comment_id'))

<div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
    <div>
        <div class="section-kicker mb-2">Discussion</div>
        <h3 class="h5 mb-0">Comments</h3>
    </div>
</div>

<div class="surface-card p-4 comment-compose mb-4">
    <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
        <div>
            <div class="fw-semibold">Add a comment</div>
            <div class="small text-muted">Use rich text to call out blockers, decisions, or follow-ups.</div>
        </div>
    </div>

    <form method="POST" action="{{ route('tasks.comments.store', $task) }}">
        @csrf
        <input type="hidden" name="return_to" value="{{ $commentReturnTo }}">

        <x-rich-text-editor
            name="body"
            label="Comment"
            :value="old('body')"
            placeholder="Write a comment for the team..."
            :rows="5"
            id="task-comment-body"
        />

        <div class="d-flex justify-content-end pt-3">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-chat-square-text me-1"></i> Add Comment
            </button>
        </div>
    </form>
</div>

<div class="d-grid gap-3">
    @forelse ($task->comments as $comment)
        @php($isAuthor = auth()->id() === $comment->user_id)
        @php($isEditing = (string) $editingCommentId === (string) $comment->id)
        <div class="surface-card p-4 comment-card">
            <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                <div class="flex-grow-1">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <div class="fw-semibold">{{ $comment->user?->name ?: 'Unknown user' }}</div>
                        <span class="comment-dot">&bull;</span>
                        <div class="small text-muted">{{ $comment->created_at->format('M d, Y h:i A') }}</div>
                    </div>

                    <div class="rich-text-content comment-body">
                        {!! $comment->body !!}
                    </div>
                </div>

                @if ($isAuthor)
                    <div class="d-flex flex-wrap align-items-start gap-2">
                        <button
                            type="button"
                            class="btn btn-outline-secondary btn-sm icon-only-btn"
                            data-bs-toggle="collapse"
                            data-bs-target="#comment-edit-{{ $comment->id }}"
                            aria-expanded="{{ $isEditing ? 'true' : 'false' }}"
                            aria-controls="comment-edit-{{ $comment->id }}"
                            aria-label="Edit comment"
                            title="Edit comment"
                        >
                            <i class="bi bi-pencil-square" aria-hidden="true"></i>
                            <span class="visually-hidden">Edit comment</span>
                        </button>

                        <button
                            type="button"
                            class="btn btn-outline-danger btn-sm icon-only-btn"
                            data-delete-confirm
                            data-delete-action="{{ route('tasks.comments.destroy', [$task, $comment]) }}"
                            data-delete-title="Delete comment"
                            data-delete-message="Delete this comment? This cannot be undone."
                            data-delete-submit="Delete Comment"
                            aria-label="Delete comment"
                            title="Delete comment"
                        >
                            <i class="bi bi-trash" aria-hidden="true"></i>
                            <span class="visually-hidden">Delete comment</span>
                        </button>
                    </div>
                @endif
            </div>

            @if ($isAuthor)
                <div class="collapse {{ $isEditing ? 'show' : '' }} mt-3" id="comment-edit-{{ $comment->id }}">
                    <form method="POST" action="{{ route('tasks.comments.update', [$task, $comment]) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="comment_id" value="{{ $comment->id }}">
                        <input type="hidden" name="return_to" value="{{ $commentReturnTo }}">

                        <x-rich-text-editor
                            name="edit_body"
                            label="Edit comment"
                            :value="$isEditing ? old('edit_body') : $comment->body"
                            placeholder="Update the comment..."
                            :rows="5"
                            :id="'task-comment-edit-'.$comment->id"
                        />

                        <div class="d-flex flex-wrap justify-content-end gap-2 pt-3">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-save me-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    @empty
        <div class="table-empty">No comments yet.</div>
    @endforelse
</div>
