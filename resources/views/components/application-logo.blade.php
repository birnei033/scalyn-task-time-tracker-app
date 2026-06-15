@props([
    'class' => '',
])

<img
    {{ $attributes->merge(['class' => trim('brand-mark '.$class)]) }}
    src="{{ asset('images/scalyn-logo.png') }}"
    alt="{{ config('app.name', 'Scalyn Task Time Tracker') }}"
>
