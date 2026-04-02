<?php

namespace App\Core\Http\Middleware;

use App\Core\Enums\Locale;
use App\Core\Models\ThemePreset;
use App\Core\Services\SettingsService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ApplyApplicationContext
{
    public function __construct(
        protected SettingsService $settings,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $preference = $user?->preference;
        $locale = $preference?->locale
            ?? $user?->locale
            ?? $this->settings->get('localization', 'default_locale', 'en');

        $themeKey = $preference?->theme_key
            ?? ThemePreset::query()->where('is_default', true)->value('key')
            ?? 'amber';

        $themeMode = $preference?->theme_mode ?? 'system';
        $localeEnum = Locale::tryFrom($locale) ?? Locale::English;

        app()->setLocale($localeEnum->value);

        View::share('kashmosContext', [
            'locale' => $localeEnum->value,
            'direction' => $localeEnum->direction(),
            'theme_key' => $themeKey,
            'theme_mode' => $themeMode,
        ]);

        return $next($request);
    }
}
