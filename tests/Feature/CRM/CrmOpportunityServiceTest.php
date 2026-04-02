<?php

namespace Tests\Feature\CRM;

use App\Modules\CRM\Models\CrmOpportunity;
use App\Modules\CRM\Models\CrmPipelineStage;
use App\Modules\CRM\Services\CrmOpportunityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmOpportunityServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_opportunity_stage_move_updates_status_and_history(): void
    {
        $this->seed();

        $service = app(CrmOpportunityService::class);
        $opportunity = CrmOpportunity::query()->where('status', 'open')->firstOrFail();
        $wonStage = CrmPipelineStage::query()->where('is_won_stage', true)->firstOrFail();

        $moved = $service->moveStage($opportunity, $wonStage, 'Client accepted final offer.');

        $this->assertSame($wonStage->id, $moved->crm_pipeline_stage_id);
        $this->assertSame('won', $moved->status);
        $this->assertNotNull($moved->won_at);
        $this->assertNull($moved->lost_at);
        $this->assertDatabaseHas('crm_stage_history', [
            'crm_opportunity_id' => $moved->id,
            'to_crm_pipeline_stage_id' => $wonStage->id,
        ]);
    }

    public function test_pipeline_stage_ordering_is_consistent(): void
    {
        $this->seed();

        $orderedCodes = CrmPipelineStage::query()
            ->orderBy('stage_order')
            ->pluck('code')
            ->all();

        $this->assertSame(['NEW', 'QUALIFIED', 'PROPOSAL', 'WON', 'LOST'], $orderedCodes);
        $this->assertSame(1, CrmPipelineStage::query()->where('is_won_stage', true)->count());
        $this->assertSame(1, CrmPipelineStage::query()->where('is_lost_stage', true)->count());
    }
}

