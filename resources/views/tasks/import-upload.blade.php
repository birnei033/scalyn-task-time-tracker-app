<x-app-layout>
    <x-slot name="header">Import Tasks</x-slot>

    <section class="page-hero p-4 p-lg-5 mb-4">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <div class="page-kicker mb-2">CSV import</div>
                <h2 class="page-title h1 mb-3">Bring tasks in from a CSV file.</h2>
                <p class="page-subtitle mb-0">
                    Upload a CSV, map its columns to task fields, and import only the rows that pass validation.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <a href="{{ route('tasks.index') }}" class="btn btn-outline-secondary btn-lg">
                    Back to Tasks
                </a>
            </div>
        </div>
    </section>

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
