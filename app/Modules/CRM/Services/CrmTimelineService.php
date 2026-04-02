<?php

namespace App\Modules\CRM\Services;

use App\Modules\CRM\Models\CrmActivity;
use App\Modules\CRM\Models\CrmAttachment;
use App\Modules\CRM\Models\CrmCall;
use App\Modules\CRM\Models\CrmEmail;
use App\Modules\CRM\Models\CrmNote;
use App\Modules\CRM\Models\CrmOpportunity;
use App\Modules\CRM\Models\CrmTask;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CrmTimelineService
{
    /**
     * @return array<int, array{
     *     type: string,
     *     title: string,
     *     description: string,
     *     actor: string,
     *     occurred_at: \Illuminate\Support\Carbon
     * }>
     */
    public function forSubject(string $subjectType, int $subjectId, int $limit = 100): array
    {
        $entries = collect()
            ->merge($this->activityEntries($subjectType, $subjectId))
            ->merge($this->taskEntries($subjectType, $subjectId))
            ->merge($this->noteEntries($subjectType, $subjectId))
            ->merge($this->attachmentEntries($subjectType, $subjectId))
            ->merge($this->emailEntries($subjectType, $subjectId))
            ->merge($this->callEntries($subjectType, $subjectId))
            ->merge($this->stageHistoryEntries($subjectType, $subjectId))
            ->sortByDesc('occurred_at')
            ->take(max(1, $limit))
            ->values();

        return $entries->all();
    }

    /**
     * @return Collection<int, array{
     *     type: string,
     *     title: string,
     *     description: string,
     *     actor: string,
     *     occurred_at: \Illuminate\Support\Carbon
     * }>
     */
    private function activityEntries(string $subjectType, int $subjectId): Collection
    {
        return CrmActivity::query()
            ->with(['owner', 'creator'])
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->get()
            ->map(fn (CrmActivity $activity): array => [
                'type' => __('crm.timeline.types.activity'),
                'title' => $activity->title,
                'description' => __('crm.timeline.activity_description', [
                    'type' => $this->activityTypeLabel($activity->activity_type),
                    'status' => $this->activityStatusLabel($activity->status),
                ]),
                'actor' => $activity->owner?->name ?? $activity->creator?->name ?? __('crm.common.system'),
                'occurred_at' => $activity->completed_at ?? $activity->due_at ?? $activity->updated_at ?? $activity->created_at,
            ]);
    }

    /**
     * @return Collection<int, array{
     *     type: string,
     *     title: string,
     *     description: string,
     *     actor: string,
     *     occurred_at: \Illuminate\Support\Carbon
     * }>
     */
    private function taskEntries(string $subjectType, int $subjectId): Collection
    {
        return CrmTask::query()
            ->with(['owner', 'creator'])
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->get()
            ->map(fn (CrmTask $task): array => [
                'type' => __('crm.timeline.types.task'),
                'title' => $task->title,
                'description' => __('crm.timeline.task_description', [
                    'priority' => $this->priorityLabel($task->priority),
                    'status' => $this->taskStatusLabel($task->status),
                ]),
                'actor' => $task->owner?->name ?? $task->creator?->name ?? __('crm.common.system'),
                'occurred_at' => $task->completed_at ?? $task->due_at ?? $task->updated_at ?? $task->created_at,
            ]);
    }

    /**
     * @return Collection<int, array{
     *     type: string,
     *     title: string,
     *     description: string,
     *     actor: string,
     *     occurred_at: \Illuminate\Support\Carbon
     * }>
     */
    private function noteEntries(string $subjectType, int $subjectId): Collection
    {
        return CrmNote::query()
            ->with('creator')
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->get()
            ->map(fn (CrmNote $note): array => [
                'type' => __('crm.timeline.types.note'),
                'title' => __('crm.timeline.note_title', [
                    'visibility' => $this->visibilityLabel($note->visibility),
                ]),
                'description' => str($note->note)->limit(180)->toString(),
                'actor' => $note->creator?->name ?? __('crm.common.system'),
                'occurred_at' => $note->updated_at ?? $note->created_at,
            ]);
    }

    /**
     * @return Collection<int, array{
     *     type: string,
     *     title: string,
     *     description: string,
     *     actor: string,
     *     occurred_at: \Illuminate\Support\Carbon
     * }>
     */
    private function attachmentEntries(string $subjectType, int $subjectId): Collection
    {
        return CrmAttachment::query()
            ->with('creator')
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->get()
            ->map(fn (CrmAttachment $attachment): array => [
                'type' => __('crm.timeline.types.attachment'),
                'title' => $attachment->file_name,
                'description' => __('crm.timeline.attachment_description', [
                    'mime' => $attachment->mime_type ?? __('crm.resources.fields.file'),
                    'size' => number_format((float) $attachment->size_bytes),
                    'bytes' => __('crm.common.bytes'),
                ]),
                'actor' => $attachment->creator?->name ?? __('crm.common.system'),
                'occurred_at' => $attachment->updated_at ?? $attachment->created_at,
            ]);
    }

    /**
     * @return Collection<int, array{
     *     type: string,
     *     title: string,
     *     description: string,
     *     actor: string,
     *     occurred_at: \Illuminate\Support\Carbon
     * }>
     */
    private function emailEntries(string $subjectType, int $subjectId): Collection
    {
        return CrmEmail::query()
            ->with(['owner', 'creator'])
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->get()
            ->map(fn (CrmEmail $email): array => [
                'type' => __('crm.timeline.types.email'),
                'title' => $email->subject_line,
                'description' => __('crm.timeline.email_description', [
                    'direction' => Str::headline((string) $email->direction),
                    'status' => Str::headline((string) $email->status),
                ]),
                'actor' => $email->owner?->name ?? $email->creator?->name ?? __('crm.common.system'),
                'occurred_at' => $email->sent_at ?? $email->updated_at ?? $email->created_at,
            ]);
    }

    /**
     * @return Collection<int, array{
     *     type: string,
     *     title: string,
     *     description: string,
     *     actor: string,
     *     occurred_at: \Illuminate\Support\Carbon
     * }>
     */
    private function callEntries(string $subjectType, int $subjectId): Collection
    {
        return CrmCall::query()
            ->with(['owner', 'creator'])
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->get()
            ->map(fn (CrmCall $call): array => [
                'type' => __('crm.timeline.types.call'),
                'title' => __('crm.timeline.call_title', [
                    'direction' => Str::headline((string) $call->direction),
                ]),
                'description' => __('crm.timeline.call_description', [
                    'status' => Str::headline((string) $call->status),
                    'duration' => (string) $call->duration_seconds,
                    'seconds' => __('crm.common.seconds'),
                ]),
                'actor' => $call->owner?->name ?? $call->creator?->name ?? __('crm.common.system'),
                'occurred_at' => $call->ended_at ?? $call->started_at ?? $call->updated_at ?? $call->created_at,
            ]);
    }

    /**
     * @return Collection<int, array{
     *     type: string,
     *     title: string,
     *     description: string,
     *     actor: string,
     *     occurred_at: \Illuminate\Support\Carbon
     * }>
     */
    private function stageHistoryEntries(string $subjectType, int $subjectId): Collection
    {
        if ($subjectType !== CrmOpportunity::class) {
            return collect();
        }

        $opportunity = CrmOpportunity::query()->with([
            'stageHistory.fromStage',
            'stageHistory.toStage',
            'stageHistory.actor',
        ])->find($subjectId);

        if (! $opportunity) {
            return collect();
        }

        return $opportunity->stageHistory->map(fn ($entry): array => [
            'type' => __('crm.timeline.types.stage'),
            'title' => __('crm.timeline.stage_title', [
                'stage' => $entry->toStage?->name ?? __('crm.common.not_available'),
            ]),
            'description' => __('crm.timeline.stage_description', [
                'from' => $entry->fromStage?->name ?? __('crm.common.not_available'),
                'to' => $entry->toStage?->name ?? __('crm.common.not_available'),
                'note' => $entry->note
                    ? __('crm.timeline.stage_note_suffix', ['note' => $entry->note])
                    : '',
            ]),
            'actor' => $entry->actor?->name ?? __('crm.common.system'),
            'occurred_at' => $entry->changed_at ?? $entry->updated_at ?? $entry->created_at,
        ]);
    }

    private function activityTypeLabel(?string $value): string
    {
        return __(
            'crm.activity_types.'.$value,
            [],
            null
        ) !== 'crm.activity_types.'.$value
            ? __('crm.activity_types.'.$value)
            : Str::headline((string) $value);
    }

    private function activityStatusLabel(?string $value): string
    {
        return __(
            'crm.activity_statuses.'.$value,
            [],
            null
        ) !== 'crm.activity_statuses.'.$value
            ? __('crm.activity_statuses.'.$value)
            : Str::headline((string) $value);
    }

    private function taskStatusLabel(?string $value): string
    {
        return __(
            'crm.task_statuses.'.$value,
            [],
            null
        ) !== 'crm.task_statuses.'.$value
            ? __('crm.task_statuses.'.$value)
            : Str::headline((string) $value);
    }

    private function priorityLabel(?string $value): string
    {
        return __(
            'crm.priorities.'.$value,
            [],
            null
        ) !== 'crm.priorities.'.$value
            ? __('crm.priorities.'.$value)
            : Str::headline((string) $value);
    }

    private function visibilityLabel(?string $value): string
    {
        return __(
            'crm.visibility.'.$value,
            [],
            null
        ) !== 'crm.visibility.'.$value
            ? __('crm.visibility.'.$value)
            : Str::headline((string) $value);
    }
}
