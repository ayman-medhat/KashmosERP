<?php

namespace App\Modules\CRM\Services;

use App\Modules\CRM\Models\CrmLead;
use App\Modules\CRM\Models\CrmOpportunity;
use App\Modules\CRM\Models\CrmPipelineStage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CrmLeadService
{
    public function create(array $attributes): CrmLead
    {
        $attributes['uuid'] ??= (string) Str::uuid();
        $attributes['lead_no'] ??= $this->nextLeadNo();
        $attributes['status'] ??= 'new';
        $attributes['created_by'] ??= auth()->id();
        $lead = CrmLead::query()->create($attributes)->refresh();

        if (! $lead->owner_id) {
            $lead = app(CrmAssignmentService::class)->assignLead($lead);
        }

        return $lead;
    }

    public function qualify(CrmLead $lead): CrmLead
    {
        if (in_array($lead->status, ['disqualified', 'converted'], true)) {
            throw new \DomainException('Disqualified or converted leads cannot be qualified.');
        }

        $lead->forceFill([
            'status' => 'qualified',
            'qualified_at' => now(),
            'disqualified_at' => null,
        ])->save();

        return $lead->refresh();
    }

    public function disqualify(CrmLead $lead): CrmLead
    {
        if ($lead->status === 'converted') {
            throw new \DomainException('Converted leads cannot be disqualified.');
        }

        $lead->forceFill([
            'status' => 'disqualified',
            'disqualified_at' => now(),
        ])->save();

        return $lead->refresh();
    }

    public function convertToOpportunity(CrmLead $lead, array $attributes = []): CrmOpportunity
    {
        return DB::transaction(function () use ($lead, $attributes): CrmOpportunity {
            $lead = CrmLead::query()->whereKey($lead->id)->lockForUpdate()->firstOrFail();

            if ($lead->status === 'converted') {
                throw new \DomainException('Lead is already converted.');
            }

            if ($lead->status === 'disqualified') {
                throw new \DomainException('Disqualified lead cannot be converted.');
            }

            $stage = isset($attributes['crm_pipeline_stage_id'])
                ? CrmPipelineStage::query()
                    ->whereKey($attributes['crm_pipeline_stage_id'])
                    ->where('is_active', true)
                    ->first()
                : CrmPipelineStage::query()
                    ->where('is_active', true)
                    ->orderBy('stage_order')
                    ->first();

            if (! $stage) {
                throw new \DomainException('No active CRM pipeline stage found for opportunity conversion.');
            }

            $opportunity = CrmOpportunity::query()->create([
                'uuid' => (string) Str::uuid(),
                'opportunity_no' => $attributes['opportunity_no'] ?? $this->nextOpportunityNo(),
                'company_profile_id' => $lead->company_profile_id,
                'name' => $attributes['name'] ?? ('Opportunity - '.$lead->name),
                'crm_account_id' => $lead->crm_account_id,
                'crm_contact_id' => $lead->crm_contact_id,
                'crm_lead_id' => $lead->id,
                'crm_pipeline_stage_id' => $stage->id,
                'status' => 'open',
                'probability' => $attributes['probability'] ?? $stage->default_probability,
                'expected_value' => $attributes['expected_value'] ?? $lead->expected_value ?? 0,
                'expected_close_date' => $attributes['expected_close_date'] ?? $lead->expected_close_date,
                'owner_id' => $attributes['owner_id'] ?? $lead->owner_id,
                'assigned_by' => auth()->id(),
                'created_by' => auth()->id(),
            ]);

            if (! $opportunity->owner_id) {
                $opportunity = app(CrmAssignmentService::class)->assignOpportunity($opportunity);
            }

            $lead->forceFill([
                'status' => 'converted',
                'converted_at' => now(),
                'converted_crm_opportunity_id' => $opportunity->id,
                'qualified_at' => $lead->qualified_at ?? now(),
            ])->save();

            $opportunity->stageHistory()->create([
                'from_crm_pipeline_stage_id' => null,
                'to_crm_pipeline_stage_id' => $opportunity->crm_pipeline_stage_id,
                'from_probability' => null,
                'to_probability' => $opportunity->probability,
                'note' => 'Opportunity created from lead conversion',
                'changed_by' => auth()->id(),
                'changed_at' => now(),
            ]);

            return $opportunity->refresh()->load(['stage', 'lead']);
        });
    }

    protected function nextLeadNo(): string
    {
        $next = (int) CrmLead::query()->count() + 1;

        return 'LEAD-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    protected function nextOpportunityNo(): string
    {
        $next = (int) CrmOpportunity::query()->count() + 1;

        return 'OPP-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
