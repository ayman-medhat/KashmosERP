<?php

namespace App\Core\Filament\Widgets;

use App\Core\Services\DashboardMetricsService;
use Filament\Widgets\ChartWidget;

class KashmosBacklogQualityWidget extends ChartWidget
{
    protected ?string $heading = 'Backlog Quality';

    protected ?string $description = 'Open approved versus partially fulfilled orders';

    protected int|string|array $columnSpan = 'full';

    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $backlog = app(DashboardMetricsService::class)->backlogQuality();

        return [
            'datasets' => [
                [
                    'label' => 'Sales Orders',
                    'data' => [
                        $backlog['open_approved_sales_orders'],
                        $backlog['partially_delivered_sales_orders'],
                    ],
                    'backgroundColor' => [
                        'rgba(245, 158, 11, 0.75)',
                        'rgba(37, 99, 235, 0.75)',
                    ],
                ],
                [
                    'label' => 'Purchase Orders',
                    'data' => [
                        $backlog['open_approved_purchase_orders'],
                        $backlog['partially_received_purchase_orders'],
                    ],
                    'backgroundColor' => [
                        'rgba(217, 119, 6, 0.75)',
                        'rgba(5, 150, 105, 0.75)',
                    ],
                ],
            ],
            'labels' => [
                'Open Approved',
                'Partially Fulfilled',
            ],
        ];
    }
}
