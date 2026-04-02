<?php

namespace Tests\Feature\CRM;

use App\Models\User;
use App\Modules\CRM\Models\CrmActivity;
use App\Modules\CRM\Models\CrmLead;
use App\Modules\CRM\Models\CrmLeadSource;
use App\Modules\CRM\Models\CrmOpportunity;
use App\Modules\CRM\Models\CrmPipelineStage;
use App\Modules\CRM\Models\CrmTask;
use App\Modules\CRM\Services\CrmReportsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class CrmReportsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_conversion_summary_applies_owner_and_source_filters(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 2, 10, 0, 0, 'UTC'));
        $this->seed();

        $owner = User::factory()->create([
            'email' => 'crm-reports-owner@kashmos.test',
            'is_active' => true,
        ]);

        $websiteSource = CrmLeadSource::query()->where('code', 'WEBSITE')->firstOrFail();
        $campaignSource = CrmLeadSource::query()->where('code', 'CAMPAIGN')->firstOrFail();

        CrmLead::query()->create([
            'uuid' => (string) Str::uuid(),
            'lead_no' => 'LEAD-930001',
            'name' => 'Website Converted',
            'status' => 'converted',
            'converted_at' => now(),
            'crm_lead_source_id' => $websiteSource->id,
            'owner_id' => $owner->id,
        ]);

        CrmLead::query()->create([
            'uuid' => (string) Str::uuid(),
            'lead_no' => 'LEAD-930002',
            'name' => 'Website Qualified',
            'status' => 'qualified',
            'crm_lead_source_id' => $websiteSource->id,
            'owner_id' => $owner->id,
        ]);

        CrmLead::query()->create([
            'uuid' => (string) Str::uuid(),
            'lead_no' => 'LEAD-930003',
            'name' => 'Campaign Disqualified',
            'status' => 'disqualified',
            'crm_lead_source_id' => $campaignSource->id,
            'owner_id' => $owner->id,
        ]);

        $summary = app(CrmReportsService::class)->conversionSummary(
            dateFrom: now()->subDay(),
            dateTo: now()->addDay(),
            ownerId: $owner->id,
            sourceId: $websiteSource->id,
        );

        $this->assertSame(2, $summary['total_leads']);
        $this->assertSame(1, $summary['qualified_leads']);
        $this->assertSame(1, $summary['converted_leads']);
        $this->assertSame(0, $summary['disqualified_leads']);
        $this->assertSame(50.0, $summary['conversion_rate']);

        Carbon::setTestNow();
    }

    public function test_pipeline_by_stage_and_owner_performance_are_consistent(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 2, 10, 0, 0, 'UTC'));
        $this->seed();

        $ownerA = User::factory()->create(['email' => 'reports-owner-a@kashmos.test', 'is_active' => true]);
        $ownerB = User::factory()->create(['email' => 'reports-owner-b@kashmos.test', 'is_active' => true]);

        $newStage = CrmPipelineStage::query()->where('code', 'NEW')->firstOrFail();
        $proposalStage = CrmPipelineStage::query()->where('code', 'PROPOSAL')->firstOrFail();
        $wonStage = CrmPipelineStage::query()->where('code', 'WON')->firstOrFail();

        CrmOpportunity::query()->create([
            'uuid' => (string) Str::uuid(),
            'opportunity_no' => 'OPP-930001',
            'name' => 'Owner A Open 1',
            'crm_pipeline_stage_id' => $newStage->id,
            'status' => 'open',
            'probability' => 20,
            'expected_value' => 100.00,
            'owner_id' => $ownerA->id,
        ]);

        CrmOpportunity::query()->create([
            'uuid' => (string) Str::uuid(),
            'opportunity_no' => 'OPP-930002',
            'name' => 'Owner A Open 2',
            'crm_pipeline_stage_id' => $proposalStage->id,
            'status' => 'open',
            'probability' => 50,
            'expected_value' => 200.00,
            'owner_id' => $ownerA->id,
        ]);

        CrmOpportunity::query()->create([
            'uuid' => (string) Str::uuid(),
            'opportunity_no' => 'OPP-930003',
            'name' => 'Owner A Won',
            'crm_pipeline_stage_id' => $wonStage->id,
            'status' => 'won',
            'probability' => 100,
            'expected_value' => 400.00,
            'won_at' => now()->subHour(),
            'owner_id' => $ownerA->id,
        ]);

        CrmOpportunity::query()->create([
            'uuid' => (string) Str::uuid(),
            'opportunity_no' => 'OPP-930004',
            'name' => 'Owner B Open',
            'crm_pipeline_stage_id' => $newStage->id,
            'status' => 'open',
            'probability' => 30,
            'expected_value' => 150.00,
            'owner_id' => $ownerB->id,
        ]);

        $lead = CrmLead::query()->where('status', 'new')->firstOrFail();
        CrmActivity::query()->create([
            'uuid' => (string) Str::uuid(),
            'subject_type' => CrmLead::class,
            'subject_id' => $lead->id,
            'title' => 'Owner A Completed Activity',
            'activity_type' => 'call',
            'status' => 'completed',
            'priority' => 'normal',
            'completed_at' => now(),
            'owner_id' => $ownerA->id,
        ]);

        $service = app(CrmReportsService::class);
        $pipelineRows = $service->pipelineByStage($ownerA->id);
        $ownerRows = $service->ownerPerformance(now()->subDay(), now()->addDay());

        $this->assertSame(2, (int) $pipelineRows->sum('opportunity_count'));
        $this->assertSame(300.0, (float) $pipelineRows->sum('open_value'));

        $ownerAPerformance = $ownerRows->firstWhere('owner', $ownerA->name);
        $this->assertNotNull($ownerAPerformance);
        $this->assertSame(2, $ownerAPerformance['open_opportunities']);
        $this->assertSame(1, $ownerAPerformance['won_opportunities']);
        $this->assertSame(400.0, (float) $ownerAPerformance['won_value']);
        $this->assertSame(1, $ownerAPerformance['completed_activities']);

        Carbon::setTestNow();
    }

    public function test_activity_summary_counts_completion_and_overdue_items(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 2, 10, 0, 0, 'UTC'));
        $this->seed();

        $owner = User::factory()->create([
            'email' => 'reports-activity-owner@kashmos.test',
            'is_active' => true,
        ]);

        $lead = CrmLead::query()->where('status', 'new')->firstOrFail();

        CrmActivity::query()->create([
            'uuid' => (string) Str::uuid(),
            'subject_type' => CrmLead::class,
            'subject_id' => $lead->id,
            'title' => 'Completed Activity',
            'activity_type' => 'meeting',
            'status' => 'completed',
            'priority' => 'normal',
            'due_at' => now()->subHours(2),
            'completed_at' => now()->subHour(),
            'owner_id' => $owner->id,
        ]);

        CrmActivity::query()->create([
            'uuid' => (string) Str::uuid(),
            'subject_type' => CrmLead::class,
            'subject_id' => $lead->id,
            'title' => 'Overdue Activity',
            'activity_type' => 'call',
            'status' => 'scheduled',
            'priority' => 'high',
            'due_at' => now()->subHour(),
            'owner_id' => $owner->id,
        ]);

        CrmTask::query()->create([
            'uuid' => (string) Str::uuid(),
            'subject_type' => CrmLead::class,
            'subject_id' => $lead->id,
            'title' => 'Completed Task',
            'status' => 'completed',
            'priority' => 'normal',
            'due_at' => now()->subHours(3),
            'completed_at' => now()->subHour(),
            'owner_id' => $owner->id,
        ]);

        CrmTask::query()->create([
            'uuid' => (string) Str::uuid(),
            'subject_type' => CrmLead::class,
            'subject_id' => $lead->id,
            'title' => 'Overdue Task',
            'status' => 'open',
            'priority' => 'urgent',
            'due_at' => now()->subMinutes(30),
            'owner_id' => $owner->id,
        ]);

        $summary = app(CrmReportsService::class)->activitySummary(
            dateFrom: now()->subDay(),
            dateTo: now()->addDay(),
            ownerId: $owner->id,
        );

        $this->assertSame(2, $summary['total_activities']);
        $this->assertSame(1, $summary['completed_activities']);
        $this->assertSame(1, $summary['overdue_activities']);
        $this->assertSame(2, $summary['total_tasks']);
        $this->assertSame(1, $summary['completed_tasks']);
        $this->assertSame(1, $summary['overdue_tasks']);
        $this->assertSame(50.0, $summary['completion_rate']);

        Carbon::setTestNow();
    }
}
