<?php

namespace App\Modules\CRM\Services;

use App\Modules\CRM\Models\CrmOpportunity;
use App\Modules\CRM\Models\CrmPipelineStage;
use Illuminate\Support\Facades\DB;

class CrmOpportunityService
{
    public function moveStage(CrmOpportunity $opportunity, CrmPipelineStage $stage, ?string $note = null): CrmOpportunity
    {
        if (! $stage->is_active) {
            throw new \DomainException('Cannot move opportunity to an inactive stage.');
        }

        return DB::transaction(function () use ($opportunity, $stage, $note): CrmOpportunity {
            $opportunity = CrmOpportunity::query()
                ->whereKey($opportunity->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($opportunity->crm_pipeline_stage_id === $stage->id) {
                return $opportunity->refresh()->load(['stage', 'stageHistory']);
            }

            $fromStageId = $opportunity->crm_pipeline_stage_id;
            $fromProbability = $opportunity->probability;

            $status = 'open';
            $wonAt = null;
            $lostAt = null;

            if ($stage->is_won_stage) {
                $status = 'won';
                $wonAt = now();
            } elseif ($stage->is_lost_stage) {
                $status = 'lost';
                $lostAt = now();
            }

            $opportunity->forceFill([
                'crm_pipeline_stage_id' => $stage->id,
                'probability' => $stage->default_probability,
                'status' => $status,
                'won_at' => $wonAt,
                'lost_at' => $lostAt,
                'last_activity_at' => now(),
            ])->save();

            $opportunity->stageHistory()->create([
                'from_crm_pipeline_stage_id' => $fromStageId,
                'to_crm_pipeline_stage_id' => $stage->id,
                'from_probability' => $fromProbability,
                'to_probability' => $stage->default_probability,
                'note' => $note,
                'changed_by' => auth()->id(),
                'changed_at' => now(),
            ]);

            return $opportunity->refresh()->load(['stage', 'stageHistory']);
        });
    }
}
