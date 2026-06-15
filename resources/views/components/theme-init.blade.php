@props([
    'source' => 'guest',
    'preference' => 'system',
])

<script>
    (() => {
        const root = document.documentElement;
        const source = @json($source);
        const preference = @json($preference);
        const storageKey = 'scalyn-theme-preference';
        const normalize = (value) => (value === 'light' || value === 'dark' ? value : null);
        const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        let storedTheme = null;

        try {
            storedTheme = normalize(window.localStorage.getItem(storageKey));
        } catch (error) {
            storedTheme = null;
        }
        const resolvedPreference = normalize(preference);

        const resolvedTheme = source === 'account'
            ? (resolvedPreference ?? systemTheme)
            : (storedTheme ?? systemTheme);

        root.dataset.bsTheme = resolvedTheme;
        root.dataset.scalynThemeSource = source;
        root.dataset.scalynThemePreference = resolvedPreference ?? 'system';
        root.dataset.scalynThemeResolved = resolvedTheme;
    })();
</script>
