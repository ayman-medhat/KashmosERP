<?php

namespace App\Modules\CRM\Services;

use App\Modules\CRM\Models\CrmActivity;
use App\Modules\CRM\Models\CrmLead;
use App\Modules\CRM\Models\CrmLeadSource;
use App\Modules\CRM\Models\CrmOpportunity;
use App\Modules\CRM\Models\CrmTask;
use Illuminate\Support\Carbon;

class CrmDashboardMetricsService
{
    /**
     * @return array{
     *     open_opportunities: int,
     *     open_pipeline_value: float,
     *     won_this_month: int,
     *     lost_this_month: int
     * }
     */
    public function pipelineSummary(): array
    {
        return [
            'open_opportunities' => CrmOpportunity::query()
                ->where('status', 'open')
                ->count(),
            'open_pipeline_value' => (float) CrmOpportunity::query()
                ->where('status', 'open')
                ->sum('expected_value'),
            'won_this_month' => CrmOpportunity::query()
                ->where('status', 'won')
                ->whereBetween('won_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                ->count(),
            'lost_this_month' => CrmOpportunity::query()
                ->where('status', 'lost')
                ->whereBetween('lost_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                ->count(),
        ];
    }

    /**
     * @return array{overdue_activities: int, overdue_tasks: int}
     */
    public function overdueActivitySummary(): array
    {
        return [
            'overdue_activities' => CrmActivity::query()
                ->whereIn('status', ['scheduled', 'overdue'])
                ->whereNotNull('due_at')
                ->where('due_at', '<', now())
                ->count(),
            'overdue_tasks' => CrmTask::query()
                ->whereIn('status', ['open', 'in_progress'])
                ->whereNotNull('due_at')
                ->where('due_at', '<', now())
                ->count(),
        ];
    }

    /**
     * @return array{converted_leads: int, total_leads: int, conversion_rate: float}
     */
    public function conversionSnapshot(): array
    {
        $totalLeads = CrmLead::query()->count();
        $converted = CrmLead::query()->where('status', 'converted')->count();
        $rate = $totalLeads > 0 ? round(($converted / $totalLeads) * 100, 2) : 0.0;

        return [
            'converted_leads' => $converted,
            'total_leads' => $totalLeads,
            'conversion_rate' => $rate,
        ];
    }

    /**
     * @return array{
     *     labels: array<int, string>,
     *     values: array<int, int>
     * }
     */
    public function sourcePerformance(): array
    {
        $rows = CrmLeadSource::query()
            ->withCount('leads')
            ->orderByDesc('leads_count')
            ->limit(8)
            ->get();

        return [
            'labels' => $rows->map(fn (CrmLeadSource $source): string => $source->name)->all(),
            'values' => $rows->map(fn (CrmLeadSource $source): int => (int) $source->leads_count)->all(),
        ];
    }

    /**
     * @return array{
     *     labels: array<int, string>,
     *     won_deals: array<int, int>
     * }
     */
    public function ownerPerformance(): array
    {
        $rows = CrmOpportunity::query()
            ->selectRaw('owner_id, COUNT(*) as won_deals')
            ->with('owner:id,name')
            ->where('status', 'won')
            ->whereNotNull('owner_id')
            ->groupBy('owner_id')
            ->orderByDesc('won_deals')
            ->limit(8)
            ->get();

        return [
            'labels' => $rows->map(fn (CrmOpportunity $row): string => $row->owner?->name ?? __('crm.common.unassigned'))->all(),
            'won_deals' => $rows->map(fn (CrmOpportunity $row): int => (int) $row->won_deals)->all(),
        ];
    }
}
