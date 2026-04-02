<?php

namespace Tests\Feature\CRM;

use App\Models\User;
use App\Modules\CRM\Models\CrmAssignmentRule;
use App\Modules\CRM\Models\CrmLead;
use App\Modules\CRM\Models\CrmOpportunity;
use App\Modules\CRM\Models\CrmPipelineStage;
use App\Modules\CRM\Services\CrmAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CrmAssignmentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_round_robin_rule_assigns_leads_in_rotation(): void
    {
        $this->seed();

        CrmAssignmentRule::query()->update(['is_active' => false]);

        $ownerA = User::factory()->create(['email' => 'owner-a@kashmos.test', 'is_active' => true]);
        $ownerB = User::factory()->create(['email' => 'owner-b@kashmos.test', 'is_active' => true]);

        CrmAssignmentRule::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Round Robin Lead Rule',
            'entity_type' => 'lead',
            'priority' => 1,
            'is_active' => true,
            'conditions' => [],
            'assignment_strategy' => 'round_robin',
            'assigned_user_ids' => [$ownerA->id, $ownerB->id],
            'created_by' => $ownerA->id,
        ]);

        $leadA = CrmLead::query()->create([
            'uuid' => (string) Str::uuid(),
            'lead_no' => 'LEAD-910001',
            'name' => 'Assignment Lead A',
            'status' => 'new',
        ]);

        $leadB = CrmLead::query()->create([
            'uuid' => (string) Str::uuid(),
            'lead_no' => 'LEAD-910002',
            'name' => 'Assignment Lead B',
            'status' => 'new',
        ]);

        $service = app(CrmAssignmentService::class);
        $assignedA = $service->assignLead($leadA);
        $assignedB = $service->assignLead($leadB);

        $this->assertSame($ownerA->id, $assignedA->owner_id);
        $this->assertSame($ownerB->id, $assignedB->owner_id);
    }

    public function test_least_loaded_rule_assigns_opportunity_to_lower_backlog_owner(): void
    {
        $this->seed();

        CrmAssignmentRule::query()->update(['is_active' => false]);

        $ownerA = User::factory()->create(['email' => 'least-a@kashmos.test', 'is_active' => true]);
        $ownerB = User::factory()->create(['email' => 'least-b@kashmos.test', 'is_active' => true]);
        $stage = CrmPipelineStage::query()->where('is_won_stage', false)->where('is_lost_stage', false)->firstOrFail();

        CrmOpportunity::query()->create([
            'uuid' => (string) Str::uuid(),
            'opportunity_no' => 'OPP-910001',
            'name' => 'Owner A Open Opportunity',
            'crm_pipeline_stage_id' => $stage->id,
            'status' => 'open',
            'probability' => 30,
            'expected_value' => 12000,
            'owner_id' => $ownerA->id,
        ]);

        CrmAssignmentRule::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Least Loaded Opportunity Rule',
            'entity_type' => 'opportunity',
            'priority' => 1,
            'is_active' => true,
            'conditions' => [],
            'assignment_strategy' => 'least_loaded',
            'assigned_user_ids' => [$ownerA->id, $ownerB->id],
            'created_by' => $ownerA->id,
        ]);

        $opportunity = CrmOpportunity::query()->create([
            'uuid' => (string) Str::uuid(),
            'opportunity_no' => 'OPP-910002',
            'name' => 'Unassigned Opportunity',
            'crm_pipeline_stage_id' => $stage->id,
            'status' => 'open',
            'probability' => 20,
            'expected_value' => 9000,
        ]);

        $assigned = app(CrmAssignmentService::class)->assignOpportunity($opportunity);

        $this->assertSame($ownerB->id, $assigned->owner_id);
    }
}

