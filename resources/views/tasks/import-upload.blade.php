<x-app-layout>
    <x-slot name="header">Import Tasks</x-slot>
    <x-slot name="actions">
        <a href="{{ route('tasks.index') }}" class="btn btn-outline-secondary btn-lg">
            Back to Tasks
        </a>
    </x-slot>

    <div class="surface-card p-4 p-lg-5">
        <form method="POST" action="{{ route('tasks.import.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="row g-4">
                <div class="col-12">
                    <label class="form-label">CSV File</label>
                    <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" accept=".csv,text/csv">
                    <div class="form-text">Upload a comma-separated CSV file with a header row.</div>
                    @error('file')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <div class="surface-card p-3 p-lg-4">
                        <div class="section-kicker mb-2">Expected fields</div>
                        <div class="row g-3">
                            <div class="col-lg-6">
                                <div class="fw-semibold">Required</div>
                                <ul class="mb-0">
                                    <li>Client</li>
                                    <li>Task Title</li>
                                </ul>
                            </div>
                            <div class="col-lg-6">
                                <div class="fw-semibold">Optional</div>
                                <ul class="mb-0">
                                    <li>Assigned User</li>
                                    <li>Description</li>
                                    <li>Status</li>
                                    <li>Priority</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 d-flex justify-content-end gap-2">
                    <a href="{{ route('tasks.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button class="btn btn-primary" type="submit" data-loading-text="Uploading...">
                        <i class="bi bi-arrow-right-circle me-1"></i> Continue
                    </button>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
