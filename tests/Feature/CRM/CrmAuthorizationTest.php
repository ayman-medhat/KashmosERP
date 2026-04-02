<?php

namespace Tests\Feature\CRM;

use App\Models\User;
use App\Modules\CRM\Models\CrmActivity;
use App\Modules\CRM\Models\CrmAssignmentRule;
use App\Modules\CRM\Models\CrmLead;
use App\Modules\CRM\Models\CrmLeadSource;
use App\Modules\CRM\Models\CrmOpportunity;
use App\Modules\CRM\Models\CrmPipelineStage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class CrmAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_crm_actions_are_denied_without_permissions(): void
    {
        $this->seed();

        $user = User::factory()->create([
            'email' => 'crm-denied@kashmos.test',
            'is_active' => true,
        ]);

        $lead = CrmLead::query()->firstOrFail();
        $opportunity = CrmOpportunity::query()->firstOrFail();
        $activity = CrmActivity::query()->firstOrFail();
        $rule = CrmAssignmentRule::query()->firstOrFail();

        $gate = Gate::forUser($user);

        $this->assertFalse($gate->allows('viewAny', CrmLead::class));
        $this->assertFalse($gate->allows('create', CrmLead::class));
        $this->assertFalse($gate->allows('convert', $lead));
        $this->assertFalse($gate->allows('moveStage', $opportunity));
        $this->assertFalse($gate->allows('complete', $activity));
        $this->assertFalse($gate->allows('create', CrmPipelineStage::class));
        $this->assertFalse($gate->allows('create', CrmLeadSource::class));
        $this->assertFalse($gate->allows('update', $rule));
    }

    public function test_crm_actions_are_allowed_with_required_permissions(): void
    {
        $this->seed();

        $user = User::factory()->create([
            'email' => 'crm-allowed@kashmos.test',
            'is_active' => true,
        ]);

        $user->givePermissionTo([
            'crm.view',
            'crm.create',
            'crm.edit',
            'crm.convert_lead',
            'crm.move_stage',
            'crm.complete_activity',
            'crm.manage_pipeline',
            'crm.manage_sources',
            'crm.manage_rules',
        ]);

        $lead = CrmLead::query()->firstOrFail();
        $opportunity = CrmOpportunity::query()->firstOrFail();
        $activity = CrmActivity::query()->firstOrFail();
        $rule = CrmAssignmentRule::query()->firstOrFail();

        $gate = Gate::forUser($user);

        $this->assertTrue($gate->allows('viewAny', CrmLead::class));
        $this->assertTrue($gate->allows('create', CrmLead::class));
        $this->assertTrue($gate->allows('convert', $lead));
        $this->assertTrue($gate->allows('moveStage', $opportunity));
        $this->assertTrue($gate->allows('complete', $activity));
        $this->assertTrue($gate->allows('create', CrmPipelineStage::class));
        $this->assertTrue($gate->allows('create', CrmLeadSource::class));
        $this->assertTrue($gate->allows('update', $rule));
    }
}
