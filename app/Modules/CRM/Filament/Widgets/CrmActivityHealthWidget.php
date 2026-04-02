<?php

namespace App\Modules\CRM\Filament\Widgets;

use App\Modules\CRM\Filament\Resources\CrmActivityResource;
use App\Modules\CRM\Filament\Resources\CrmTaskResource;
use App\Modules\CRM\Services\CrmDashboardMetricsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CrmActivityHealthWidget extends StatsOverviewWidget
{
    protected ?string $heading = null;

    public function getHeading(): ?string
    {
        return __('crm.widgets.activity_health.heading');
    }

    protected function getStats(): array
    {
        $metrics = app(CrmDashboardMetricsService::class)->overdueActivitySummary();

        return [
            Stat::make(__('crm.widgets.activity_health.overdue_activities'), (string) $metrics['overdue_activities'])
                ->description(__('crm.widgets.activity_health.overdue_activities_description'))
                ->color($metrics['overdue_activities'] > 0 ? 'danger' : 'gray')
                ->url(CrmActivityResource::getUrl('index', [
                    'tableFilters' => [
                        'overdue' => [
                            'isActive' => true,
                        ],
                    ],
                ])),
            Stat::make(__('crm.widgets.activity_health.overdue_tasks'), (string) $metrics['overdue_tasks'])
                ->description(__('crm.widgets.activity_health.overdue_tasks_description'))
                ->color($metrics['overdue_tasks'] > 0 ? 'danger' : 'gray')
                ->url(CrmTaskResource::getUrl('index', [
                    'tableFilters' => [
                        'overdue' => [
                            'isActive' => true,
                        ],
                    ],
                ])),
        ];
    }
}
