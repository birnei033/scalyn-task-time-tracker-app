@php($task = $task ?? new \App\Models\Task)
@php($canManageTask = auth()->user()->canManageTeam())

<div class="row g-3">
    @if ($canManageTask || ! $task->exists)
        <div class="col-12">
            <label class="form-label">Task Title</label>
            <input
                class="form-control @error('title') is-invalid @enderror"
                name="title"
                value="{{ old('title', $task->title) }}"
                required
            >
            @error('title')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>

        <div class="col-lg-6">
            <label class="form-label">Client</label>
            <select class="form-select @error('client_id') is-invalid @enderror" name="client_id" required>
                <option value="">Choose client</option>
                @foreach ($clients as $client)
                    <option value="{{ $client->id }}" @selected(old('client_id', $task->client_id) == $client->id)>{{ $client->name }}</option>
                @endforeach
            </select>
            @error('client_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-lg-6">
            <label class="form-label">Assigned User</label>
            <select class="form-select @error('assigned_user_id') is-invalid @enderror" name="assigned_user_id">
                <option value="">Unassigned</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected(old('assigned_user_id', $task->assigned_user_id) == $user->id)>{{ $user->name }}</option>
                @endforeach
            </select>
            @error('assigned_user_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <x-rich-text-editor
                name="description"
                label="Task Description"
                :value="$task->description"
                placeholder="Describe the work, goals, and context for this task."
                :rows="7"
                id="task-description"
            />
        </div>
    @endif

    <div class="col-lg-6">
        <label class="form-label">Status</label>
        <select class="form-select @error('status') is-invalid @enderror" name="status">
            @foreach (\App\Models\Task::statusOptions() as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $task->status ?: 'open') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>

    @if ($canManageTask || ! $task->exists)
        <div class="col-lg-6">
            <label class="form-label">Priority</label>
            <select class="form-select @error('priority') is-invalid @enderror" name="priority">
                @foreach (['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('priority', $task->priority ?: 'medium') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('priority')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
    @endif

    @if ($task->exists)
        <div class="col-12">
            @unless ($canManageTask)
                <div class="alert alert-info border-0 mb-0">
                    You can update the status and add a progress report for this assigned task.
                </div>
            @endunless
        </div>

        <div class="col-12">
            <x-rich-text-editor
                name="progress_notes"
                label="Progress Report"
                :value="old('progress_notes')"
                placeholder="Summarize progress, blockers, and next steps."
                :rows="6"
                id="task-progress-notes"
            />
            <div class="form-text">This will be saved as a dated progress update on the task.</div>
        </div>
    @endif

    <div class="col-12">
        <div class="surface-card p-3 p-lg-4">
            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <h2 class="h6 mb-1">Attachments</h2>
                    <p class="text-muted small mb-0">Upload images, PDFs, and documents for the task.</p>
                </div>
                <span class="badge badge-soft">Private task files</span>
            </div>

            <input
                type="file"
                name="attachments[]"
                class="form-control @error('attachments') is-invalid @enderror @error('attachments.*') is-invalid @enderror"
                multiple
                accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.rtf,.odt,.ods,.odp"
            >
            <div class="form-text">You can select multiple files. Existing attachments will stay linked to the task.</div>
            @error('attachments')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            @error('attachments.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror

            @if ($task->exists)
                <div class="mt-4">
                    <div class="section-kicker mb-2">Current files</div>
                    @forelse ($task->attachments as $attachment)
                        <div class="attachment-card mb-2">
                            <div class="attachment-icon">
                                <i class="bi {{ $attachment->isImage() ? 'bi-image' : 'bi-paperclip' }}"></i>
                            </div>
                            <div class="attachment-meta">
                                <div class="attachment-title">{{ $attachment->original_name }}</div>
                                <div class="attachment-subtitle">
                                    {{ $attachment->displaySize() }} - {{ $attachment->mime_type ?: 'Unknown type' }}
                                    @if ($attachment->uploader)
                                        - Uploaded by {{ $attachment->uploader->name }}
                                    @endif
                                </div>
                            </div>
                            <a class="btn btn-outline-primary btn-sm" href="{{ route('tasks.attachments.show', [$task, $attachment]) }}" target="_blank" rel="noopener">
                                View
                            </a>
                            <a class="btn btn-primary btn-sm" href="{{ route('tasks.attachments.download', [$task, $attachment]) }}">
                                Download
                            </a>
                        </div>
                    @empty
                        <div class="table-empty text-start">No attachments yet.</div>
                    @endforelse
                </div>
            @endif
        </div>
    </div>
</div>
