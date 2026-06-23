<div class="row g-4">
    <div class="col-lg-4">
        <div class="surface-card p-4 h-100">
            <div class="section-kicker mb-2">Summary</div>
            <h3 class="h5 mb-3">Task metadata</h3>
            <div class="d-grid gap-2">
                <div><strong>Client:</strong> {{ $task->client->name }}</div>
                <div><strong>Assigned:</strong> {{ $task->assignedUser?->name ?: 'Unassigned' }}</div>
                <div class="d-flex align-items-center gap-2"><strong>Status:</strong> <span class="badge {{ $task->statusBadgeClass() }}">{{ $task->statusLabel() }}</span></div>
                <div class="d-flex align-items-center gap-2">
                    <strong>Priority:</strong>
                    @can('update', $task)
                        <button
                            type="button"
                            class="btn btn-link p-0 text-decoration-none align-baseline"
                            aria-label="Update priority for {{ $task->title }}"
                            data-task-priority-trigger
                            data-task-id="{{ $task->id }}"
                            data-task-title="{{ $task->title }}"
                            data-task-client="{{ $task->client->name }}"
                            data-task-priority="{{ $task->priority }}"
                            data-task-priority-label="{{ $task->priorityLabel() }}"
                            data-task-priority-class="{{ $task->priorityBadgeClass() }}"
                            data-task-priority-action="{{ route('tasks.priority.update', $task) }}"
                            data-task-return-to="{{ request()->fullUrl() }}"
                        >
                            <span class="badge {{ $task->priorityBadgeClass() }}">{{ $task->priorityLabel() }}</span>
                        </button>
                    @else
                        <span class="badge {{ $task->priorityBadgeClass() }}">{{ $task->priorityLabel() }}</span>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="surface-card p-3 p-lg-4 task-detail-tabs">
            <ul class="nav nav-tabs task-tabs" id="task-detail-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button
                        class="nav-link active"
                        id="task-description-tab"
                        data-bs-toggle="tab"
                        data-bs-target="#task-description-pane"
                        type="button"
                        role="tab"
                        aria-controls="task-description-pane"
                        aria-selected="true"
                    >
                        Task Description
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button
                        class="nav-link"
                        id="logged-time-tab"
                        data-bs-toggle="tab"
                        data-bs-target="#logged-time-pane"
                        type="button"
                        role="tab"
                        aria-controls="logged-time-pane"
                        aria-selected="false"
                    >
                        Logged Time
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button
                        class="nav-link"
                        id="comments-tab"
                        data-bs-toggle="tab"
                        data-bs-target="#comments-pane"
                        type="button"
                        role="tab"
                        aria-controls="comments-pane"
                        aria-selected="false"
                    >
                        Comments
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button
                        class="nav-link"
                        id="activity-history-tab"
                        data-bs-toggle="tab"
                        data-bs-target="#activity-history-pane"
                        type="button"
                        role="tab"
                        aria-controls="activity-history-pane"
                        aria-selected="false"
                    >
                        Activity History
                    </button>
                </li>
            </ul>

            <div class="tab-content pt-4" id="task-detail-tabs-content">
                <div
                    class="tab-pane fade show active"
                    id="task-description-pane"
                    role="tabpanel"
                    aria-labelledby="task-description-tab"
                    tabindex="0"
                >
                    <div class="section-kicker mb-2">Description</div>
                    <h3 class="h5 mb-3">Task description</h3>
                    <div class="rich-text-content">
                        {!! $task->description ?: '<p class="text-muted mb-0">No description provided.</p>' !!}
                    </div>

                    <div class="task-detail-section">
                        <h3 class="h5 mb-3">Attachments</h3>
                        @forelse ($task->attachments as $attachment)
                            <div class="attachment-card mb-2">
                                <div class="attachment-icon">
                                    <i class="bi {{ $attachment->isImage() ? 'bi-image' : 'bi-paperclip' }}"></i>
                                </div>
                                <div class="attachment-meta">
                                    <div class="attachment-title">{{ $attachment->original_name }}</div>
                                    <div class="attachment-subtitle">
                                        {{ $attachment->displaySize() }} &middot; {{ $attachment->mime_type ?: 'Unknown type' }}
                                        @if ($attachment->uploader)
                                            &middot; Uploaded by {{ $attachment->uploader->name }}
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
                            <p class="muted-copy mb-0">No attachments uploaded yet.</p>
                        @endforelse
                    </div>
                </div>

                <div
                    class="tab-pane fade"
                    id="logged-time-pane"
                    role="tabpanel"
                    aria-labelledby="logged-time-tab"
                    tabindex="0"
                >
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
                        <div>
                            <div class="section-kicker mb-2">Time Tracking</div>
                            <h3 class="h5 mb-0">Logged time</h3>
                        </div>
                        @can('update', $task)
                            <button
                                type="button"
                                class="btn btn-outline-success"
                                data-task-log-time-trigger
                                data-task-id="{{ $task->id }}"
                                data-task-title="{{ $task->title }}"
                                data-task-client="{{ $task->client->name }}"
                                data-task-status="{{ $task->status }}"
                                data-task-status-label="{{ $task->statusLabel() }}"
                                data-task-status-class="{{ $task->statusBadgeClass() }}"
                            >
                                <i class="bi bi-stopwatch me-1"></i> Log Time
                            </button>
                        @endcan
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>User</th>
                                    <th>Notes</th>
                                    <th class="text-end">Time</th>
                                    <th class="text-end"><span class="visually-hidden">Actions</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($task->timeEntries as $entry)
                                    <tr>
                                        <td>{{ $entry->date->format('M d, Y') }}</td>
                                        <td>
                                            <x-user-identity :name="$entry->user->name" seed="{{ $entry->user_id }}" />
                                        </td>
                                        <td>{{ \App\Support\RichText::excerpt($entry->notes) }}</td>
                                        <td class="text-end fw-semibold">{{ \App\Support\TimeDisplay::formatHours($entry->hours) }}</td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-2">
                                                @can('update', $entry)
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-outline-secondary"
                                                        aria-label="Edit time entry for {{ $entry->task->title }}"
                                                        data-time-entry-edit-trigger
                                                        data-time-entry-edit-action="{{ route('time-entries.update', $entry) }}"
                                                        data-time-entry-edit-id="{{ $entry->id }}"
                                                        data-time-entry-edit-user-id="{{ $entry->user_id }}"
                                                        data-time-entry-edit-task-id="{{ $entry->task_id }}"
                                                        data-time-entry-edit-date="{{ $entry->date->toDateString() }}"
                                                        data-time-entry-edit-minutes="{{ \App\Support\TimeDisplay::hoursToMinutes($entry->hours) }}"
                                                        data-time-entry-edit-notes="{{ e($entry->notes ?? '') }}"
                                                        data-time-entry-edit-return-to="{{ route('tasks.show', $task).'#logged-time-pane' }}"
                                                    >
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                @endcan
                                                @can('delete', $entry)
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-outline-danger"
                                                        data-delete-confirm
                                                        data-delete-action="{{ route('time-entries.destroy', $entry) }}"
                                                        data-delete-title="Delete Logged Time"
                                                        data-delete-message="Delete this logged time entry for {{ $entry->task->title }}? This action cannot be undone."
                                                        data-delete-submit="Delete Time Entry"
                                                        data-delete-return-to="{{ route('tasks.show', $task).'#logged-time-pane' }}"
                                                        aria-label="Delete time entry for {{ $entry->task->title }}"
                                                    >
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5">
                                            <div class="table-empty">No time logged for this task yet.</div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div
                    class="tab-pane fade"
                    id="comments-pane"
                    role="tabpanel"
                    aria-labelledby="comments-tab"
                    tabindex="0"
                >
                    @include('tasks._comments')
                </div>

                <div
                    class="tab-pane fade"
                    id="activity-history-pane"
                    role="tabpanel"
                    aria-labelledby="activity-history-tab"
                    tabindex="0"
                >
                    <div class="section-kicker mb-2">History</div>
                    <h3 class="h5 mb-3">Activity history</h3>

                    @if (! empty($hasTaskActivityTable))
                        <div class="d-grid gap-2 mb-4">
                            @forelse ($task->activityEntries as $activity)
                                <div class="border rounded-3 p-3">
                                    <div class="d-flex justify-content-between gap-3">
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold">{{ $activity->summary() }}</div>
                                            @if ($activity->action === 'updated' && $activity->field)
                                                <div class="small text-muted mt-1">From: {{ $activity->formattedOldValue() }}</div>
                                                <div class="small text-muted">To: {{ $activity->formattedNewValue() }}</div>
                                            @endif
                                        </div>
                                        <div class="text-end small text-muted text-nowrap">
                                            <div>{{ $activity->user?->name ?: 'System' }}</div>
                                            <div>{{ $activity->created_at->format('M d, Y h:i A') }}</div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="table-empty text-start">No task activity recorded yet.</div>
                            @endforelse
                        </div>
                    @else
                        <div class="alert alert-light border mb-4">
                            Task activity history will appear after the audit table is available.
                        </div>
                    @endif

                    <div class="task-detail-section">
                        <h3 class="h5 mb-3">Progress history</h3>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>User</th>
                                        <th>Update</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @forelse ($task->progressEntries as $update)
                                    <tr>
                                        <td>{{ $update->date->format('M d, Y') }}</td>
                                        <td>
                                            <x-user-identity :name="$update->user->name" seed="{{ $update->user_id }}" />
                                        </td>
                                        <td>{{ \App\Support\RichText::excerpt($update->notes) }}</td>
                                    </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3">
                                                <div class="table-empty">No progress updates yet.</div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
