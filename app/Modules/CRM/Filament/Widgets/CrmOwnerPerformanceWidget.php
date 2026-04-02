<?php

namespace App\Modules\CRM\Filament\Widgets;

use App\Modules\CRM\Services\CrmDashboardMetricsService;
use Filament\Widgets\ChartWidget;

class CrmOwnerPerformanceWidget extends ChartWidget
{
    protected ?string $heading = null;

    protected ?string $description = null;

    protected int|string|array $columnSpan = 'full';

    protected function getType(): string
    {
        return 'line';
    }

    public function getHeading(): ?string
    {
        return __('crm.widgets.owner_performance.heading');
    }

    public function getDescription(): ?string
    {
        return __('crm.widgets.owner_performance.description');
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $data = app(CrmDashboardMetricsService::class)->ownerPerformance();

        return [
            'datasets' => [
                [
                    'label' => __('crm.widgets.owner_performance.dataset_label'),
                    'data' => $data['won_deals'],
                    'borderColor' => '#059669',
                    'backgroundColor' => 'rgba(5, 150, 105, 0.15)',
                    'tension' => 0.35,
                ],
            ],
            'labels' => $data['labels'],
        ];
    }
}
