@php($context = $kashmosContext ?? ['locale' => 'en', 'direction' => 'ltr', 'theme_key' => 'amber', 'theme_mode' => 'system'])
<script>
    (() => {
        const locale = @js($context['locale']);
        const direction = @js($context['direction']);
        const themeKey = @js($context['theme_key']);
        const themeMode = @js($context['theme_mode']);
        const root = document.documentElement;

        root.lang = locale;
        root.dir = direction;
        root.dataset.kashmosTheme = themeKey;

        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const dark = themeMode === 'dark' || (themeMode === 'system' && prefersDark);
        root.classList.toggle('dark', dark);
    })();
</script>
<style>
    :root[data-kashmos-theme="amber"] {
        --kashmos-primary-50: 255 251 235;
        --kashmos-primary-500: 217 119 6;
        --kashmos-primary-600: 180 83 9;
    }

    :root[data-kashmos-theme="emerald"] {
        --kashmos-primary-50: 236 253 245;
        --kashmos-primary-500: 16 185 129;
        --kashmos-primary-600: 5 150 105;
    }

    :root[data-kashmos-theme="blue"] {
        --kashmos-primary-50: 239 246 255;
        --kashmos-primary-500: 37 99 235;
        --kashmos-primary-600: 29 78 216;
    }

    :root {
        --primary-50: rgb(var(--kashmos-primary-50));
        --primary-500: rgb(var(--kashmos-primary-500));
        --primary-600: rgb(var(--kashmos-primary-600));
    }
</style>
