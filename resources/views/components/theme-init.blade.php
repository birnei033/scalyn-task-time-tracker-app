@props([
    'theme' => 'light',
])

<script>
    (() => {
        const root = document.documentElement;
        const resolvedTheme = @json($theme);

        root.dataset.bsTheme = resolvedTheme;
        root.dataset.scalynThemeResolved = resolvedTheme;
    })();
</script>
