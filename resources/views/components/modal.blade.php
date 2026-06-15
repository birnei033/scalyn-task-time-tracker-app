@props([
    'name',
    'show' => false,
    'maxWidth' => '2xl',
])

@php
$maxWidth = [
    'sm' => 'modal-sm',
    'md' => '',
    'lg' => 'modal-lg',
    'xl' => 'modal-xl',
    '2xl' => 'modal-xl',
][$maxWidth];
@endphp

<div
    {{ $attributes->merge([
        'class' => trim('modal fade'),
        'id' => $name,
        'tabindex' => '-1',
        'aria-labelledby' => $name.'-label',
        'aria-hidden' => 'true',
    ]) }}
    @if ($show) data-auto-open="true" @endif
>
    <div class="modal-dialog modal-dialog-scrollable {{ $maxWidth }}">
        <div class="modal-content">
            {{ $slot }}
        </div>
    </div>
</div>
