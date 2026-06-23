@props([
    'name' => null,
    'href' => null,
    'placeholder' => 'Unassigned',
    'seed' => null,
])

@php
    $displayName = filled($name) ? trim((string) $name) : $placeholder;
    $seedValue = filled($seed) ? (string) $seed : $displayName;
    $avatarClasses = [
        'task-assignee-avatar-1',
        'task-assignee-avatar-2',
        'task-assignee-avatar-3',
        'task-assignee-avatar-4',
        'task-assignee-avatar-5',
    ];
    $avatarClass = $avatarClasses[abs(crc32($seedValue)) % count($avatarClasses)];
    $parts = preg_split('/\s+/', trim($displayName)) ?: [];
    $initials = collect($parts)
        ->filter()
        ->map(fn ($part) => mb_substr($part, 0, 1))
        ->take(2)
        ->implode('');
    $initials = $initials ?: mb_substr($displayName, 0, 1);
@endphp

<span {{ $attributes->class(['task-assignee']) }}>
    <span class="task-assignee-avatar {{ $avatarClass }}">{{ $initials }}</span>

    @if ($href)
        <a href="{{ $href }}" class="task-assignee-name text-decoration-none">{{ $displayName }}</a>
    @else
        <span class="task-assignee-name">{{ $displayName }}</span>
    @endif
</span>
