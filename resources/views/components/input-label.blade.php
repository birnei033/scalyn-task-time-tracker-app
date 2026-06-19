@props([
    'value',
    'required' => false,
])

<label {{ $attributes->merge(['class' => 'form-label']) }}>
    {{ $value ?? $slot }}
    @if ($required)
        <x-required-indicator />
    @endif
</label>
