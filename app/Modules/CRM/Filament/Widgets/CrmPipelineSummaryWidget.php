<?php

namespace App\Modules\CRM\Filament\Widgets;

use App\Modules\CRM\Filament\Resources\CrmLeadResource;
use App\Modules\CRM\Filament\Resources\CrmOpportunityResource;
use App\Modules\CRM\Services\CrmDashboardMetricsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CrmPipelineSummaryWidget extends StatsOverviewWidget
{
    protected ?string $heading = null;

    public function getHeading(): ?string
    {
        return __('crm.widgets.pipeline_summary.heading');
    }

    protected function getStats(): array
    {
        $pipeline = app(CrmDashboardMetricsService::class)->pipelineSummary();
        $conversion = app(CrmDashboardMetricsService::class)->conversionSnapshot();

        return [
            Stat::make(__('crm.widgets.pipeline_summary.open_opportunities'), (string) $pipeline['open_opportunities'])
                ->description(__('crm.widgets.pipeline_summary.open_opportunities_description'))
                ->color('primary')
                ->url(CrmOpportunityResource::getUrl('index', [
                    'tableFilters' => [
                        'status' => [
                            'value' => 'open',
                        ],
                    ],
                ])),
            Stat::make(__('crm.widgets.pipeline_summary.open_pipeline_value'), number_format($pipeline['open_pipeline_value'], 2).' EGP')
                ->description(__('crm.widgets.pipeline_summary.open_pipeline_value_description'))
                ->color('success'),
            Stat::make(__('crm.widgets.pipeline_summary.won_this_month'), (string) $pipeline['won_this_month'])
                ->description(__('crm.widgets.pipeline_summary.won_this_month_description'))
                ->color('success'),
            Stat::make(__('crm.widgets.pipeline_summary.lead_conversion'), number_format($conversion['conversion_rate'], 2).'%')
                ->description(__('crm.widgets.pipeline_summary.lead_conversion_description', [
                    'converted' => $conversion['converted_leads'],
                    'total' => $conversion['total_leads'],
                ]))
                ->color('warning')
                ->url(CrmLeadResource::getUrl('index', [
                    'tableFilters' => [
                        'status' => [
                            'value' => 'converted',
                        ],
                    ],
                ])),
        ];
    }
}
