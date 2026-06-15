<x-app-layout>
    <x-slot name="header">Map Task Columns</x-slot>

    <section class="page-hero p-4 p-lg-5 mb-4">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <div class="page-kicker mb-2">Step 2 of 2</div>
                <h2 class="page-title h1 mb-3">Map CSV columns to task fields.</h2>
                <p class="page-subtitle mb-0">
                    Match the columns from your uploaded file to the fields the task importer understands.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <a href="{{ route('tasks.import.create') }}" class="btn btn-outline-secondary btn-lg">
                    Choose Another File
                </a>
            </div>
        </div>
    </section>

    <div class="surface-card p-4 p-lg-5 mb-4">
        <div class="section-kicker mb-2">Preview</div>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                    <tr>
                        @foreach ($headers as $header)
                            <th>{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sampleRows as $row)
                        <tr>
                            @foreach ($headers as $header)
                                <td>{{ $row[$header] ?? '' }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($headers) }}">No sample rows were found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="surface-card p-4 p-lg-5">
        <form method="POST" action="{{ route('tasks.import.process', $importToken) }}">
            @csrf

            <div class="row g-4">
                @foreach ($fields as $field => $definition)
                    <div class="col-lg-6">
                        <label class="form-label">{{ $definition['label'] }}</label>
                        <select name="mapping[{{ $field }}]" class="form-select @error('mapping.'.$field) is-invalid @enderror" {{ $definition['required'] ? 'required' : '' }}>
                            <option value="">Do not map</option>
                            @foreach ($headers as $header)
                                <option value="{{ $header }}" @selected(old('mapping.'.$field, $defaultMapping[$field] ?? null) === $header)>{{ $header }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">
                            {{ $definition['required'] ? 'This field is required for the import.' : 'Leave unmapped to use the default or skip the value.' }}
                        </div>
                        @error('mapping.'.$field)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                @endforeach

                <div class="col-12 d-flex justify-content-end gap-2">
                    <a href="{{ route('tasks.import.create') }}" class="btn btn-outline-secondary">Back</a>
                    <button class="btn btn-primary" type="submit" data-loading-text="Importing...">
                        <i class="bi bi-upload me-1"></i> Import Tasks
                    </button>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
