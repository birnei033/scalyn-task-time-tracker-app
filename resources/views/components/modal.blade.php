@props([
    'name',
    'show' => false,
    'maxWidth' => '2xl',
])

@php
$maxWidth = [
    'sm' => '26rem',
    'md' => '32rem',
    'lg' => '42rem',
    'xl' => '52rem',
    '2xl' => '60rem',
][$maxWidth];
@endphp

<div
    {{ $attributes->merge([
        'class' => trim('d-none'),
        'id' => $name,
        'aria-hidden' => 'true',
    ]) }}
    data-swal-source="true"
    data-swal-width="{{ $maxWidth }}"
    @if ($show) data-swal-auto-open="true" @endif
>
    <div class="swal-source-shell" style="display: none;">
        {{ $slot }}
    </div>
</div>
