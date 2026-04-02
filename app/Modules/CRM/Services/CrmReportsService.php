<?php

namespace App\Modules\CRM\Services;

use App\Modules\CRM\Models\CrmActivity;
use App\Modules\CRM\Models\CrmLead;
use App\Modules\CRM\Models\CrmOpportunity;
use App\Modules\CRM\Models\CrmPipelineStage;
use App\Modules\CRM\Models\CrmTask;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CrmReportsService
{
    public function leadsQuery(?Carbon $dateFrom = null, ?Carbon $dateTo = null, ?int $ownerId = null, ?int $sourceId = null): Builder
    {
        return CrmLead::query()
            ->when($dateFrom, fn (Builder $query): Builder => $query->whereDate('created_at', '>=', $dateFrom->toDateString()))
            ->when($dateTo, fn (Builder $query): Builder => $query->whereDate('created_at', '<=', $dateTo->toDateString()))
            ->when($ownerId, fn (Builder $query): Builder => $query->where('owner_id', $ownerId))
            ->when($sourceId, fn (Builder $query): Builder => $query->where('crm_lead_source_id', $sourceId));
    }

    public function opportunitiesQuery(?Carbon $dateFrom = null, ?Carbon $dateTo = null, ?int $ownerId = null): Builder
    {
        return CrmOpportunity::query()
            ->when($dateFrom, fn (Builder $query): Builder => $query->whereDate('created_at', '>=', $dateFrom->toDateString()))
            ->when($dateTo, fn (Builder $query): Builder => $query->whereDate('created_at', '<=', $dateTo->toDateString()))
            ->when($ownerId, fn (Builder $query): Builder => $query->where('owner_id', $ownerId));
    }

    public function activitiesQuery(?Carbon $dateFrom = null, ?Carbon $dateTo = null, ?int $ownerId = null): Builder
    {
        return CrmActivity::query()
            ->when($dateFrom, fn (Builder $query): Builder => $query->whereDate('created_at', '>=', $dateFrom->toDateString()))
            ->when($dateTo, fn (Builder $query): Builder => $query->whereDate('created_at', '<=', $dateTo->toDateString()))
            ->when($ownerId, fn (Builder $query): Builder => $query->where('owner_id', $ownerId));
    }

    /**
     * @return array{
     *     total_leads: int,
     *     qualified_leads: int,
     *     converted_leads: int,
     *     disqualified_leads: int,
     *     conversion_rate: float
     * }
     */
    public function conversionSummary(?Carbon $dateFrom = null, ?Carbon $dateTo = null, ?int $ownerId = null, ?int $sourceId = null): array
    {
        $query = $this->leadsQuery($dateFrom, $dateTo, $ownerId, $sourceId);

        $total = (clone $query)->count();
        $qualified = (clone $query)->where('status', 'qualified')->count();
        $converted = (clone $query)->where('status', 'converted')->count();
        $disqualified = (clone $query)->where('status', 'disqualified')->count();

        return [
            'total_leads' => $total,
            'qualified_leads' => $qualified,
            'converted_leads' => $converted,
            'disqualified_leads' => $disqualified,
            'conversion_rate' => $total > 0 ? round(($converted / $total) * 100, 2) : 0.0,
        ];
    }

    /**
     * @return Collection<int, array{
     *     stage: string,
     *     opportunity_count: int,
     *     open_value: float,
     *     weighted_value: float
     * }>
     */
    public function pipelineByStage(?int $ownerId = null): Collection
    {
        $rows = CrmPipelineStage::query()
            ->with(['opportunities' => function ($query) use ($ownerId): void {
                $query
                    ->when($ownerId, fn (Builder $builder): Builder => $builder->where('owner_id', $ownerId))
                    ->where('status', 'open');
            }])
            ->where('is_active', true)
            ->orderBy('stage_order')
            ->get();

        return $rows->map(function (CrmPipelineStage $stage): array {
            $opportunities = $stage->opportunities;
            $openValue = (float) $opportunities->sum('expected_value');

            $weightedValue = (float) $opportunities->sum(
                fn (CrmOpportunity $opportunity): float => ((float) $opportunity->expected_value) * (((float) $opportunity->probability) / 100)
            );

            return [
                'stage' => $stage->name,
                'opportunity_count' => $opportunities->count(),
                'open_value' => round($openValue, 2),
                'weighted_value' => round($weightedValue, 2),
            ];
        });
    }

    /**
     * @return array{
     *     total_activities: int,
     *     completed_activities: int,
     *     overdue_activities: int,
     *     total_tasks: int,
     *     completed_tasks: int,
     *     overdue_tasks: int,
     *     completion_rate: float
     * }
     */
    public function activitySummary(?Carbon $dateFrom = null, ?Carbon $dateTo = null, ?int $ownerId = null): array
    {
        $activityQuery = $this->activitiesQuery($dateFrom, $dateTo, $ownerId);
        $taskQuery = CrmTask::query()
            ->when($dateFrom, fn (Builder $query): Builder => $query->whereDate('created_at', '>=', $dateFrom->toDateString()))
            ->when($dateTo, fn (Builder $query): Builder => $query->whereDate('created_at', '<=', $dateTo->toDateString()))
            ->when($ownerId, fn (Builder $query): Builder => $query->where('owner_id', $ownerId));

        $totalActivities = (clone $activityQuery)->count();
        $completedActivities = (clone $activityQuery)->where('status', 'completed')->count();
        $overdueActivities = (clone $activityQuery)
            ->whereIn('status', ['scheduled', 'overdue'])
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->count();

        $totalTasks = (clone $taskQuery)->count();
        $completedTasks = (clone $taskQuery)->where('status', 'completed')->count();
        $overdueTasks = (clone $taskQuery)
            ->whereIn('status', ['open', 'in_progress'])
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->count();

        $totalTracked = $totalActivities + $totalTasks;
        $totalCompleted = $completedActivities + $completedTasks;

        return [
            'total_activities' => $totalActivities,
            'completed_activities' => $completedActivities,
            'overdue_activities' => $overdueActivities,
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'overdue_tasks' => $overdueTasks,
            'completion_rate' => $totalTracked > 0 ? round(($totalCompleted / $totalTracked) * 100, 2) : 0.0,
        ];
    }

    /**
     * @return Collection<int, array{
     *     owner: string,
     *     open_opportunities: int,
     *     won_opportunities: int,
     *     won_value: float,
     *     completed_activities: int
     * }>
     */
    public function ownerPerformance(?Carbon $dateFrom = null, ?Carbon $dateTo = null): Collection
    {
        $opportunities = $this->opportunitiesQuery($dateFrom, $dateTo)
            ->with('owner:id,name')
            ->whereNotNull('owner_id')
            ->get();

        $activities = $this->activitiesQuery($dateFrom, $dateTo)
            ->where('status', 'completed')
            ->whereNotNull('owner_id')
            ->selectRaw('owner_id, COUNT(*) as completed_count')
            ->groupBy('owner_id')
            ->pluck('completed_count', 'owner_id');

        return $opportunities
            ->groupBy('owner_id')
            ->map(function (Collection $ownerRows, int $ownerId) use ($activities): array {
                /** @var CrmOpportunity $first */
                $first = $ownerRows->first();

                return [
                    'owner' => $first->owner?->name ?? __('crm.common.unassigned'),
                    'open_opportunities' => $ownerRows->where('status', 'open')->count(),
                    'won_opportunities' => $ownerRows->where('status', 'won')->count(),
                    'won_value' => round((float) $ownerRows->where('status', 'won')->sum('expected_value'), 2),
                    'completed_activities' => (int) ($activities[$ownerId] ?? 0),
                ];
            })
            ->sortByDesc('won_value')
            ->values();
    }
}
