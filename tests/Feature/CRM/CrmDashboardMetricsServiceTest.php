<?php

namespace Tests\Feature\CRM;

use App\Modules\CRM\Models\CrmOpportunity;
use App\Modules\CRM\Models\CrmPipelineStage;
use App\Modules\CRM\Services\CrmDashboardMetricsService;
use App\Modules\CRM\Services\CrmOpportunityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmDashboardMetricsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_metrics_match_seeded_crm_dataset(): void
    {
        $this->seed();

        $service = app(CrmDashboardMetricsService::class);

        $pipeline = $service->pipelineSummary();
        $overdue = $service->overdueActivitySummary();
        $conversion = $service->conversionSnapshot();

        $this->assertSame(2, $pipeline['open_opportunities']);
        $this->assertSame(210000.0, $pipeline['open_pipeline_value']);
        $this->assertSame(0, $pipeline['won_this_month']);
        $this->assertSame(0, $pipeline['lost_this_month']);

        $this->assertSame(0, $overdue['overdue_activities']);
        $this->assertSame(0, $overdue['overdue_tasks']);

        $this->assertSame(1, $conversion['converted_leads']);
        $this->assertSame(3, $conversion['total_leads']);
        $this->assertSame(33.33, $conversion['conversion_rate']);
    }

    public function test_owner_performance_updates_after_winning_opportunity(): void
    {
        $this->seed();

        $opportunity = CrmOpportunity::query()->where('status', 'open')->firstOrFail();
        $wonStage = CrmPipelineStage::query()->where('is_won_stage', true)->firstOrFail();
        app(CrmOpportunityService::class)->moveStage($opportunity, $wonStage, 'Won from metrics test');

        $ownerPerformance = app(CrmDashboardMetricsService::class)->ownerPerformance();

        $this->assertNotEmpty($ownerPerformance['labels']);
        $this->assertNotEmpty($ownerPerformance['won_deals']);
        $this->assertSame(1, max($ownerPerformance['won_deals']));
    }
}

