<?php

namespace App\Core\Filament\Widgets;

use App\Core\Services\DashboardMetricsService;
use Filament\Widgets\ChartWidget;

class KashmosOperationsTrendWidget extends ChartWidget
{
    protected ?string $heading = 'Operations Trend (Last 7 Days)';

    protected ?string $description = 'Sales deliveries versus purchase receipts';

    protected int|string|array $columnSpan = 'full';

    protected function getType(): string
    {
        return 'line';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $trend = app(DashboardMetricsService::class)->operationsTrend(7);

        return [
            'datasets' => [
                [
                    'label' => 'Sales Deliveries',
                    'data' => $trend['sales_deliveries'],
                    'borderColor' => '#2563eb',
                    'backgroundColor' => 'rgba(37, 99, 235, 0.15)',
                    'tension' => 0.35,
                ],
                [
                    'label' => 'Purchase Receipts',
                    'data' => $trend['purchase_receipts'],
                    'borderColor' => '#059669',
                    'backgroundColor' => 'rgba(5, 150, 105, 0.15)',
                    'tension' => 0.35,
                ],
            ],
            'labels' => $trend['labels'],
        ];
    }
}
