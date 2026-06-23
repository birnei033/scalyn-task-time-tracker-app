@php
    $previewKind = $attachment->isImage() ? 'image' : (strtolower((string) $attachment->mime_type) === 'application/pdf' ? 'pdf' : 'file');
    $replaceModalName = 'attachment-replace-modal-'.$attachment->id;
    $historyModalName = 'attachment-history-modal-'.$attachment->id;
    $isDeleted = $attachment->trashed();
    $canManageAttachments = $canManageAttachments ?? false;
    $returnTo = request()->fullUrl();
    $formatBytes = function (int $bytes): string {
        $bytes = max(0, $bytes);

        if ($bytes < 1024) {
            return $bytes.' B';
        }

        $kilobytes = $bytes / 1024;

        if ($kilobytes < 1024) {
            return number_format($kilobytes, 1).' KB';
        }

        return number_format($kilobytes / 1024, 1).' MB';
    };
@endphp

<div class="attachment-card mb-2 {{ $isDeleted ? 'opacity-75' : '' }}">
    <div class="attachment-icon">
        <i class="bi {{ $attachment->isImage() ? 'bi-image' : 'bi-paperclip' }}"></i>
    </div>
    <div class="attachment-meta">
        <div class="attachment-title d-flex flex-wrap align-items-center gap-2">
            <span>{{ $attachment->original_name }}</span>
            @if ($isDeleted)
                <span class="badge text-bg-secondary">Deleted</span>
            @endif
        </div>
        <div class="attachment-subtitle">
            {{ $attachment->displaySize() }} &middot; {{ $attachment->mime_type ?: 'Unknown type' }}
            @if ($attachment->uploader)
                &middot; Uploaded by {{ $attachment->uploader->name }}
            @endif
            @if ($isDeleted)
                &middot; Removed from active attachments
            @endif
        </div>
    </div>

    <div class="attachment-actions d-flex flex-wrap justify-content-end gap-2 ms-auto">
        @if (! $isDeleted)
            <button
                type="button"
                class="btn btn-outline-primary btn-sm"
                data-attachment-view-trigger
                data-attachment-preview-kind="{{ $previewKind }}"
                data-attachment-preview-title="{{ $attachment->original_name }}"
                data-attachment-preview-size="{{ $attachment->displaySize() }}"
                data-attachment-preview-mime="{{ $attachment->mime_type ?: 'Unknown type' }}"
                data-attachment-preview-uploader="{{ $attachment->uploader?->name ?: 'Unknown uploader' }}"
                data-attachment-preview-view-url="{{ route('tasks.attachments.show', [$task, $attachment]) }}"
                data-attachment-preview-download-url="{{ route('tasks.attachments.download', [$task, $attachment]) }}"
            >
                View
            </button>
            <a class="btn btn-primary btn-sm" href="{{ route('tasks.attachments.download', [$task, $attachment]) }}">
                Download
            </a>
        @endif

        @if ($canManageAttachments)
            <button
                type="button"
                class="btn btn-outline-secondary btn-sm"
                data-swal-open="{{ $historyModalName }}"
            >
                History
            </button>
            @if (! $isDeleted)
                <button
                    type="button"
                    class="btn btn-outline-secondary btn-sm"
                    data-swal-open="{{ $replaceModalName }}"
                >
                    Replace
                </button>
                <button
                    type="button"
                    class="btn btn-outline-danger btn-sm"
                    data-delete-confirm
                    data-delete-action="{{ route('tasks.attachments.destroy', [$task, $attachment]) }}"
                    data-delete-title="Delete Attachment"
                    data-delete-message="Delete {{ $attachment->original_name }}? The file will be soft deleted and can be restored from version history."
                    data-delete-submit="Delete Attachment"
                    data-delete-return-to="{{ $returnTo }}"
                    aria-label="Delete {{ $attachment->original_name }}"
                >
                    Delete
                </button>
            @endif
        @endif
    </div>
</div>

@if ($canManageAttachments && ! $isDeleted)
    <x-item-modal-form
        :name="$replaceModalName"
        title="Replace Attachment"
        :action="route('tasks.attachments.replace', [$task, $attachment])"
        submitLabel="Replace Attachment"
        method="POST"
        :show="old('modal_form') === $replaceModalName"
        maxWidth="md"
    >
        <input type="hidden" name="return_to" value="{{ $returnTo }}">

        <div class="alert alert-light border mb-3">
            <div class="fw-semibold">{{ $attachment->original_name }}</div>
            <div class="small text-muted">
                {{ $attachment->displaySize() }} &middot; {{ $attachment->mime_type ?: 'Unknown type' }}
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">New File <x-required-indicator /></label>
            <input
                type="file"
                name="attachment"
                class="form-control @error('attachment') is-invalid @enderror"
                accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.rtf,.odt,.ods,.odp"
                required
            >
            <div class="form-text">The current file will be archived as a previous version.</div>
            @error('attachment')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
    </x-item-modal-form>
@endif

@if ($canManageAttachments)
    <x-modal :name="$historyModalName" maxWidth="xl">
        <div class="modal-header">
            <div>
                <div class="section-kicker mb-1">Version History</div>
                <h2 class="modal-title fs-5 mb-0">{{ $attachment->original_name }}</h2>
            </div>
            <button type="button" class="btn-close" data-swal-close aria-label="Close"></button>
        </div>

        <div class="modal-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Version</th>
                            <th>Action</th>
                            <th>Actor</th>
                            <th>Date</th>
                            <th class="text-end">Size</th>
                            <th class="text-end"><span class="visually-hidden">Restore</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($attachment->versions as $version)
                            <tr>
                                <td class="fw-semibold">{{ $version->original_name }}</td>
                                <td>
                                    <span class="badge text-bg-light border text-capitalize">{{ str_replace('_', ' ', $version->action) }}</span>
                                </td>
                                <td>{{ $version->actor?->name ?: 'System' }}</td>
                                <td>{{ $version->created_at?->format('M d, Y h:i A') }}</td>
                                <td class="text-end">{{ $formatBytes((int) $version->size) }}</td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('tasks.attachments.versions.restore', [$task, $attachment, $version]) }}" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="return_to" value="{{ $returnTo }}">
                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-outline-primary task-action-icon-btn"
                                            data-loading-text="Restoring..."
                                            aria-label="Restore this version"
                                            title="Restore this version"
                                        >
                                            <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="table-empty text-start mb-0">No version history yet.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-swal-close>
                Close
            </button>
        </div>
    </x-modal>
@endif
