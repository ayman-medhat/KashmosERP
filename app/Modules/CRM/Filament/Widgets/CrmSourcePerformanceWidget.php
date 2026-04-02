<?php

namespace App\Modules\CRM\Filament\Widgets;

use App\Modules\CRM\Services\CrmDashboardMetricsService;
use Filament\Widgets\ChartWidget;

class CrmSourcePerformanceWidget extends ChartWidget
{
    protected ?string $heading = null;

    protected ?string $description = null;

    protected int|string|array $columnSpan = 'full';

    protected function getType(): string
    {
        return 'bar';
    }

    public function getHeading(): ?string
    {
        return __('crm.widgets.source_performance.heading');
    }

    public function getDescription(): ?string
    {
        return __('crm.widgets.source_performance.description');
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $data = app(CrmDashboardMetricsService::class)->sourcePerformance();

        return [
            'datasets' => [
                [
                    'label' => __('crm.widgets.source_performance.dataset_label'),
                    'data' => $data['values'],
                    'backgroundColor' => [
                        'rgba(37, 99, 235, 0.75)',
                        'rgba(5, 150, 105, 0.75)',
                        'rgba(245, 158, 11, 0.75)',
                        'rgba(217, 70, 239, 0.75)',
                        'rgba(239, 68, 68, 0.75)',
                        'rgba(14, 165, 233, 0.75)',
                        'rgba(22, 163, 74, 0.75)',
                        'rgba(249, 115, 22, 0.75)',
                    ],
                ],
            ],
            'labels' => $data['labels'],
        ];
    }
}
