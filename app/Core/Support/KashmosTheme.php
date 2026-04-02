<?php

namespace App\Core\Support;

use App\Core\Filament\Widgets\KashmosOverviewWidget;

class KashmosTheme
{
    public static function palette(string $key): array
    {
        return match ($key) {
            'blue' => [
                '50' => '#eff6ff',
                '500' => '#2563eb',
                '600' => '#1d4ed8',
                '700' => '#1e40af',
            ],
            'emerald' => [
                '50' => '#ecfdf5',
                '500' => '#10b981',
                '600' => '#059669',
                '700' => '#047857',
            ],
            default => [
                '50' => '#fffbeb',
                '500' => '#d97706',
                '600' => '#b45309',
                '700' => '#92400e',
            ],
        };
    }

    public static function dashboardWidget(): string
    {
        return KashmosOverviewWidget::class;
    }
}
