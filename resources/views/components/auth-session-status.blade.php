@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'alert alert-success border-0 shadow-sm mb-0']) }}>
        {{ $status }}
    </div>
@endif
