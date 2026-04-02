<?php

namespace Tests\Feature\CRM;

use App\Models\User;
use App\Modules\CRM\Models\CrmAssignmentRule;
use App\Modules\CRM\Models\CrmLead;
use App\Modules\CRM\Models\CrmLeadSource;
use App\Modules\CRM\Models\CrmOpportunity;
use App\Modules\CRM\Services\CrmLeadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CrmLeadServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_lead_creation_sets_defaults_and_applies_assignment_rule(): void
    {
        $this->seed();

        CrmAssignmentRule::query()->update(['is_active' => false]);

        $owner = User::factory()->create([
            'email' => 'crm-owner@kashmos.test',
            'is_active' => true,
        ]);

        CrmAssignmentRule::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Lead Assignment',
            'entity_type' => 'lead',
            'priority' => 1,
            'is_active' => true,
            'conditions' => [],
            'assignment_strategy' => 'manual',
            'assigned_user_ids' => [$owner->id],
            'created_by' => $owner->id,
        ]);

        $lead = app(CrmLeadService::class)->create([
            'name' => 'Fresh Retail Lead',
            'email' => 'lead@retail.test',
            'crm_lead_source_id' => CrmLeadSource::query()->firstOrFail()->id,
        ]);

        $this->assertStringStartsWith('LEAD-', $lead->lead_no);
        $this->assertSame('new', $lead->status);
        $this->assertSame($owner->id, $lead->owner_id);
    }

    public function test_lead_can_be_qualified_and_disqualified(): void
    {
        $this->seed();

        $service = app(CrmLeadService::class);
        $lead = $service->create([
            'name' => 'Qualification Flow',
            'email' => 'qualification@kashmos.test',
        ]);

        $qualified = $service->qualify($lead);
        $this->assertSame('qualified', $qualified->status);
        $this->assertNotNull($qualified->qualified_at);
        $this->assertNull($qualified->disqualified_at);

        $disqualified = $service->disqualify($service->create([
            'name' => 'Disqualification Flow',
            'email' => 'disqualification@kashmos.test',
        ]));

        $this->assertSame('disqualified', $disqualified->status);
        $this->assertNotNull($disqualified->disqualified_at);
    }

    public function test_qualified_lead_can_be_converted_to_opportunity(): void
    {
        $this->seed();

        $service = app(CrmLeadService::class);
        $lead = $service->qualify($service->create([
            'name' => 'Lead To Opportunity',
            'email' => 'convert@kashmos.test',
            'expected_value' => 50000,
        ]));

        $opportunity = $service->convertToOpportunity($lead, [
            'name' => 'Converted Opportunity',
        ]);

        $this->assertInstanceOf(CrmOpportunity::class, $opportunity);
        $this->assertSame('open', $opportunity->status);
        $this->assertSame($lead->id, $opportunity->crm_lead_id);

        $lead->refresh();
        $this->assertSame('converted', $lead->status);
        $this->assertNotNull($lead->converted_at);
        $this->assertSame($opportunity->id, $lead->converted_crm_opportunity_id);
        $this->assertDatabaseHas('crm_stage_history', [
            'crm_opportunity_id' => $opportunity->id,
            'to_crm_pipeline_stage_id' => $opportunity->crm_pipeline_stage_id,
        ]);
    }

    public function test_deleted_lead_is_soft_deleted(): void
    {
        $this->seed();

        $lead = CrmLead::query()->firstOrFail();
        $lead->delete();

        $this->assertSoftDeleted('crm_leads', ['id' => $lead->id]);
    }
}
