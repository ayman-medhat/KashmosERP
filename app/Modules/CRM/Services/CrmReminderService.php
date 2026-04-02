<?php

namespace App\Modules\CRM\Services;

use App\Core\Models\User;
use App\Modules\CRM\Models\CrmActivity;
use App\Modules\CRM\Models\CrmLead;
use App\Modules\CRM\Models\CrmOpportunity;
use App\Modules\CRM\Models\CrmTask;
use App\Modules\CRM\Notifications\CrmReminderDigestNotification;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;

class CrmReminderService
{
    /**
     * @return array{
     *     overdue_activities_marked: int,
     *     owners_with_due_items: int,
     *     owners_notified: int,
     *     notifications_sent: int
     * }
     */
    public function process(?CarbonInterface $now = null, int $lookAheadHours = 24): array
    {
        $now = ($now ?? now())->copy();
        $lookAheadHours = max(1, min(168, $lookAheadHours));
        $windowEnd = $now->copy()->addHours($lookAheadHours);

        $overdueActivitiesMarked = $this->markOverdueActivities($now);
        $payloads = $this->buildOwnerPayloads($now, $windowEnd);
        $owners = User::query()
            ->whereIn('id', array_keys($payloads))
            ->where('is_active', true)
            ->whereNotNull('email')
            ->get()
            ->keyBy('id');

        $ownersNotified = 0;
        $notificationsSent = 0;

        foreach ($payloads as $ownerId => $payload) {
            /** @var User|null $owner */
            $owner = $owners->get($ownerId);
            if (! $owner) {
                continue;
            }

            if (! $this->shouldNotifyOwner($owner, $now)) {
                continue;
            }

            $owner->notify(
                (new CrmReminderDigestNotification(
                    metrics: $payload['metrics'],
                    lookAheadHours: $lookAheadHours,
                ))->locale($owner->locale ?: app()->getLocale()),
            );

            $ownersNotified++;
            $notificationsSent++;
        }

        return [
            'overdue_activities_marked' => $overdueActivitiesMarked,
            'owners_with_due_items' => count($payloads),
            'owners_notified' => $ownersNotified,
            'notifications_sent' => $notificationsSent,
        ];
    }

    private function markOverdueActivities(CarbonInterface $now): int
    {
        return CrmActivity::query()
            ->where('status', 'scheduled')
            ->whereNotNull('due_at')
            ->where('due_at', '<', $now)
            ->update(['status' => 'overdue']);
    }

    /**
     * @return array<int, array{metrics: array<string, int|float>}>
     */
    private function buildOwnerPayloads(CarbonInterface $now, CarbonInterface $windowEnd): array
    {
        $payloads = [];

        $this->accumulateCounts(
            $payloads,
            CrmActivity::query()
                ->select(['owner_id'])
                ->whereNotNull('owner_id')
                ->where('status', 'overdue')
                ->whereNotNull('due_at')
                ->where('due_at', '<=', $now)
                ->get(),
            'overdue_activities',
        );

        $this->accumulateCounts(
            $payloads,
            CrmActivity::query()
                ->select(['owner_id'])
                ->whereNotNull('owner_id')
                ->where('status', 'scheduled')
                ->whereNotNull('due_at')
                ->whereBetween('due_at', [$now, $windowEnd])
                ->get(),
            'upcoming_activities',
        );

        $this->accumulateCounts(
            $payloads,
            CrmTask::query()
                ->select(['owner_id'])
                ->whereNotNull('owner_id')
                ->whereIn('status', ['open', 'in_progress'])
                ->whereNotNull('due_at')
                ->where('due_at', '<', $now)
                ->get(),
            'overdue_tasks',
        );

        $this->accumulateCounts(
            $payloads,
            CrmTask::query()
                ->select(['owner_id'])
                ->whereNotNull('owner_id')
                ->whereIn('status', ['open', 'in_progress'])
                ->whereNotNull('due_at')
                ->whereBetween('due_at', [$now, $windowEnd])
                ->get(),
            'upcoming_tasks',
        );

        $this->accumulateCounts(
            $payloads,
            CrmLead::query()
                ->select(['owner_id'])
                ->whereNotNull('owner_id')
                ->whereIn('status', ['new', 'qualified'])
                ->whereNotNull('next_follow_up_at')
                ->where('next_follow_up_at', '<', $now)
                ->get(),
            'overdue_lead_follow_ups',
        );

        $this->accumulateCounts(
            $payloads,
            CrmLead::query()
                ->select(['owner_id'])
                ->whereNotNull('owner_id')
                ->whereIn('status', ['new', 'qualified'])
                ->whereNotNull('next_follow_up_at')
                ->whereBetween('next_follow_up_at', [$now, $windowEnd])
                ->get(),
            'upcoming_lead_follow_ups',
        );

        $this->accumulateCounts(
            $payloads,
            CrmOpportunity::query()
                ->select(['owner_id'])
                ->whereNotNull('owner_id')
                ->where('status', 'open')
                ->whereNotNull('next_follow_up_at')
                ->where('next_follow_up_at', '<', $now)
                ->get(),
            'overdue_opportunity_follow_ups',
        );

        $this->accumulateCounts(
            $payloads,
            CrmOpportunity::query()
                ->select(['owner_id'])
                ->whereNotNull('owner_id')
                ->where('status', 'open')
                ->whereNotNull('next_follow_up_at')
                ->whereBetween('next_follow_up_at', [$now, $windowEnd])
                ->get(),
            'upcoming_opportunity_follow_ups',
        );

        foreach ($payloads as $ownerId => $payload) {
            $metrics = $payload['metrics'];
            $totalDueItems = (int) (
                $metrics['overdue_activities']
                + $metrics['upcoming_activities']
                + $metrics['overdue_tasks']
                + $metrics['upcoming_tasks']
                + $metrics['overdue_lead_follow_ups']
                + $metrics['upcoming_lead_follow_ups']
                + $metrics['overdue_opportunity_follow_ups']
                + $metrics['upcoming_opportunity_follow_ups']
            );

            $payloads[$ownerId]['metrics']['total_due_items'] = $totalDueItems;
        }

        return array_filter(
            $payloads,
            fn (array $payload): bool => (($payload['metrics']['total_due_items'] ?? 0) > 0),
        );
    }

    /**
     * @param array<int, array{metrics: array<string, int|float>}> $payloads
     */
    private function accumulateCounts(array &$payloads, iterable $records, string $metricKey): void
    {
        foreach ($records as $record) {
            $ownerId = (int) ($record->owner_id ?? 0);
            if ($ownerId <= 0) {
                continue;
            }

            if (! isset($payloads[$ownerId])) {
                $payloads[$ownerId] = [
                    'metrics' => $this->emptyMetrics(),
                ];
            }

            $payloads[$ownerId]['metrics'][$metricKey]++;
        }
    }

    /**
     * @return array<string, int|float>
     */
    private function emptyMetrics(): array
    {
        return [
            'overdue_activities' => 0,
            'upcoming_activities' => 0,
            'overdue_tasks' => 0,
            'upcoming_tasks' => 0,
            'overdue_lead_follow_ups' => 0,
            'upcoming_lead_follow_ups' => 0,
            'overdue_opportunity_follow_ups' => 0,
            'upcoming_opportunity_follow_ups' => 0,
            'total_due_items' => 0,
        ];
    }

    private function shouldNotifyOwner(User $owner, CarbonInterface $now): bool
    {
        $key = sprintf('crm_reminder_owner_%d_%s', $owner->id, $now->format('YmdH'));

        return Cache::add($key, true, $now->copy()->addHour());
    }
}
