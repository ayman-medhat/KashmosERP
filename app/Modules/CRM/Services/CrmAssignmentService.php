<?php

namespace App\Modules\CRM\Services;

use App\Modules\CRM\Models\CrmAssignmentRule;
use App\Modules\CRM\Models\CrmLead;
use App\Modules\CRM\Models\CrmOpportunity;
use Illuminate\Support\Facades\Cache;

class CrmAssignmentService
{
    public function assignLead(CrmLead $lead): CrmLead
    {
        if ($lead->owner_id) {
            return $lead;
        }

        $rule = $this->firstMatchingRule('lead', $lead);

        if (! $rule) {
            return $lead;
        }

        $ownerId = $this->resolveOwnerId($rule, 'lead');
        if (! $ownerId) {
            return $lead;
        }

        $lead->forceFill([
            'owner_id' => $ownerId,
            'assigned_by' => auth()->id(),
        ])->save();

        return $lead->refresh();
    }

    public function assignOpportunity(CrmOpportunity $opportunity): CrmOpportunity
    {
        if ($opportunity->owner_id) {
            return $opportunity;
        }

        $rule = $this->firstMatchingRule('opportunity', $opportunity);

        if (! $rule) {
            return $opportunity;
        }

        $ownerId = $this->resolveOwnerId($rule, 'opportunity');
        if (! $ownerId) {
            return $opportunity;
        }

        $opportunity->forceFill([
            'owner_id' => $ownerId,
            'assigned_by' => auth()->id(),
        ])->save();

        return $opportunity->refresh();
    }

    private function firstMatchingRule(string $entityType, object $entity): ?CrmAssignmentRule
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, CrmAssignmentRule> $rules */
        $rules = CrmAssignmentRule::query()
            ->where('entity_type', $entityType)
            ->where('is_active', true)
            ->orderBy('priority')
            ->orderBy('id')
            ->get();

        foreach ($rules as $rule) {
            if ($this->matchesRule($rule, $entity)) {
                return $rule;
            }
        }

        return null;
    }

    private function matchesRule(CrmAssignmentRule $rule, object $entity): bool
    {
        $conditions = $rule->conditions ?? [];
        if ($conditions === []) {
            return true;
        }

        foreach ($conditions as $attribute => $expected) {
            $actual = data_get($entity, $attribute);

            if (is_array($expected)) {
                $normalizedActual = $this->normalizeConditionValue($actual);
                $normalizedExpected = collect($expected)
                    ->map(fn (mixed $value): mixed => $this->normalizeConditionValue($value))
                    ->all();

                if (! in_array($normalizedActual, $normalizedExpected, true)) {
                    return false;
                }

                continue;
            }

            if ($this->normalizeConditionValue($actual) !== $this->normalizeConditionValue($expected)) {
                return false;
            }
        }

        return true;
    }

    private function normalizeConditionValue(mixed $value): mixed
    {
        if (is_string($value)) {
            $trimmed = trim($value);

            if ($trimmed === '') {
                return null;
            }

            if (is_numeric($trimmed)) {
                return (string) ((float) $trimmed);
            }

            return mb_strtolower($trimmed);
        }

        if (is_int($value) || is_float($value)) {
            return (string) ((float) $value);
        }

        if (is_bool($value) || $value === null) {
            return $value;
        }

        return $value;
    }

    private function resolveOwnerId(CrmAssignmentRule $rule, string $entityType): ?int
    {
        $userIds = collect($rule->assigned_user_ids ?? [])
            ->filter(fn (mixed $value): bool => is_int($value) || ctype_digit((string) $value))
            ->map(fn (mixed $value): int => (int) $value)
            ->values();

        if ($userIds->isEmpty()) {
            return null;
        }

        $strategy = $rule->assignment_strategy;

        if ($strategy === 'manual') {
            return $userIds->first();
        }

        if ($strategy === 'least_loaded') {
            return $this->leastLoadedOwnerId($entityType, $userIds->all());
        }

        return $this->roundRobinOwnerId($rule->id, $userIds->all());
    }

    /**
     * @param array<int, int> $userIds
     */
    private function roundRobinOwnerId(int $ruleId, array $userIds): ?int
    {
        if ($userIds === []) {
            return null;
        }

        $key = "crm_assignment_rule_{$ruleId}_last_index";
        $index = (int) Cache::get($key, -1) + 1;
        Cache::forever($key, $index);

        return $userIds[$index % count($userIds)];
    }

    /**
     * @param array<int, int> $userIds
     */
    private function leastLoadedOwnerId(string $entityType, array $userIds): ?int
    {
        if ($userIds === []) {
            return null;
        }

        if ($entityType === 'opportunity') {
            return CrmOpportunity::query()
                ->selectRaw('owner_id, COUNT(*) as aggregate')
                ->whereIn('owner_id', $userIds)
                ->where('status', 'open')
                ->groupBy('owner_id')
                ->pluck('aggregate', 'owner_id')
                ->pipe(function ($counts) use ($userIds): ?int {
                    $minUserId = null;
                    $minCount = null;

                    foreach ($userIds as $userId) {
                        $count = (int) ($counts[$userId] ?? 0);
                        if ($minCount === null || $count < $minCount) {
                            $minCount = $count;
                            $minUserId = $userId;
                        }
                    }

                    return $minUserId;
                });
        }

        return CrmLead::query()
            ->selectRaw('owner_id, COUNT(*) as aggregate')
            ->whereIn('owner_id', $userIds)
            ->whereIn('status', ['new', 'qualified'])
            ->groupBy('owner_id')
            ->pluck('aggregate', 'owner_id')
            ->pipe(function ($counts) use ($userIds): ?int {
                $minUserId = null;
                $minCount = null;

                foreach ($userIds as $userId) {
                    $count = (int) ($counts[$userId] ?? 0);
                    if ($minCount === null || $count < $minCount) {
                        $minCount = $count;
                        $minUserId = $userId;
                    }
                }

                return $minUserId;
            });
    }
}
