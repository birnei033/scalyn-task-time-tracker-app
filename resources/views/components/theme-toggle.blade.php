@props([
    'type' => 'button',
])

<button
    type="{{ $type }}"
    class="btn btn-outline-secondary btn-sm theme-toggle"
    data-theme-toggle
    aria-label="{{ __('Toggle theme') }}"
>
    <i class="bi bi-circle-half me-1"></i>
    <span>{{ __('Theme') }}</span>
</button>
