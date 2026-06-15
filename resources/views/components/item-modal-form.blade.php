@props([
    'name',
    'title',
    'action',
    'submitLabel',
    'mode' => 'modal',
    'show' => false,
    'method' => 'POST',
    'cancelUrl' => null,
    'cancelLabel' => 'Cancel',
    'maxWidth' => '2xl',
])

@if ($mode === 'modal')
    <x-modal :name="$name" :show="$show" :maxWidth="$maxWidth">
        <div class="modal-header">
            <div>
                <h2 class="modal-title fs-5" id="{{ $name }}-label">{{ $title }}</h2>
                <div class="text-muted small">Complete the form and save your changes.</div>
            </div>

            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ $cancelLabel }}"></button>
        </div>

        <div class="modal-body">
            <form method="POST" action="{{ $action }}" enctype="multipart/form-data">
                @csrf
                @if ($method !== 'POST')
                    @method($method)
                @endif

                <input type="hidden" name="modal_form" value="{{ $name }}">

                {{ $slot }}

                <div class="d-flex flex-wrap justify-content-end gap-2 pt-4 mt-4 border-top">
                    <button class="btn btn-primary" type="submit" data-loading-text="{{ $submitLabel }}...">
                        <i class="bi bi-save"></i> {{ $submitLabel }}
                    </button>

                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        {{ $cancelLabel }}
                    </button>
                </div>
            </form>
        </div>
    </x-modal>
@else
    <section class="surface-card p-4 p-lg-5">
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
            <div>
                <div class="section-kicker mb-2">Form</div>
                <h2 class="page-title h3 mb-2" id="{{ $name }}-label">{{ $title }}</h2>
                <p class="muted-copy mb-0">Complete the form and save your changes.</p>
            </div>

            <a class="btn btn-outline-secondary btn-sm" href="{{ $cancelUrl }}" aria-label="{{ $cancelLabel }}">
                <i class="bi bi-arrow-left me-1"></i>
                {{ $cancelLabel }}
            </a>
        </div>

        <form method="POST" action="{{ $action }}" enctype="multipart/form-data">
            @csrf
            @if ($method !== 'POST')
                @method($method)
            @endif

            {{ $slot }}

            <div class="d-flex flex-wrap justify-content-end gap-2 pt-4 mt-4 border-top">
                <button class="btn btn-primary" type="submit" data-loading-text="{{ $submitLabel }}...">
                    <i class="bi bi-save"></i> {{ $submitLabel }}
                </button>

                <a class="btn btn-outline-secondary" href="{{ $cancelUrl }}">{{ $cancelLabel ?? 'Cancel' }}</a>
            </div>
        </form>
    </section>
@endif
