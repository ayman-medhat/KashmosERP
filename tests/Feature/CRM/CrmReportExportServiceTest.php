<?php

namespace Tests\Feature\CRM;

use App\Models\User;
use App\Modules\CRM\Models\CrmActivity;
use App\Modules\CRM\Models\CrmOpportunity;
use App\Modules\CRM\Models\CrmPipelineStage;
use App\Modules\CRM\Services\CrmReportExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class CrmReportExportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_conversion_csv_contains_expected_metric_rows(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 2, 12, 0, 0, 'UTC'));
        $this->seed();

        $csv = app(CrmReportExportService::class)->conversionCsv(
            dateFrom: now()->subDays(30),
            dateTo: now()->addDay(),
        );

        $this->assertStringStartsWith('crm_conversion_report_', $csv['filename']);
        $this->assertSame([
            __('crm.reports.csv.metric'),
            __('crm.reports.csv.value'),
        ], $csv['headers']);
        $this->assertCount(5, $csv['rows']);
        $this->assertSame(__('crm.reports.metrics.total_leads'), $csv['rows'][0][0]);
        $this->assertMatchesRegularExpression('/^\d+\.\d{2}%$/', $csv['rows'][4][1]);

        Carbon::setTestNow();
    }

    public function test_pipeline_and_owner_csv_include_structured_rows(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 2, 12, 0, 0, 'UTC'));
        $this->seed();

        $owner = User::factory()->create([
            'email' => 'export-owner@kashmos.test',
            'is_active' => true,
        ]);

        $newStage = CrmPipelineStage::query()->where('code', 'NEW')->firstOrFail();
        $wonStage = CrmPipelineStage::query()->where('code', 'WON')->firstOrFail();

        CrmOpportunity::query()->create([
            'uuid' => (string) Str::uuid(),
            'opportunity_no' => 'OPP-940001',
            'name' => 'Owner Export Open',
            'crm_pipeline_stage_id' => $newStage->id,
            'status' => 'open',
            'probability' => 25,
            'expected_value' => 5000.00,
            'owner_id' => $owner->id,
        ]);

        CrmOpportunity::query()->create([
            'uuid' => (string) Str::uuid(),
            'opportunity_no' => 'OPP-940002',
            'name' => 'Owner Export Won',
            'crm_pipeline_stage_id' => $wonStage->id,
            'status' => 'won',
            'probability' => 100,
            'expected_value' => 7000.00,
            'won_at' => now()->subDay(),
            'owner_id' => $owner->id,
        ]);

        $lead = \App\Modules\CRM\Models\CrmLead::query()->firstOrFail();
        CrmActivity::query()->create([
            'uuid' => (string) Str::uuid(),
            'subject_type' => \App\Modules\CRM\Models\CrmLead::class,
            'subject_id' => $lead->id,
            'title' => 'Owner Export Completed Activity',
            'activity_type' => 'call',
            'status' => 'completed',
            'priority' => 'normal',
            'completed_at' => now(),
            'owner_id' => $owner->id,
        ]);

        $exportService = app(CrmReportExportService::class);
        $pipelineCsv = $exportService->pipelineCsv($owner->id);
        $ownerCsv = $exportService->ownerPerformanceCsv(now()->subDays(30), now()->addDay());
        $activityCsv = $exportService->activityCsv(now()->subDays(30), now()->addDay(), $owner->id);
        $sourceCsv = $exportService->sourcePerformanceCsv(now()->subDays(30), now()->addDay());
        $allCsv = $exportService->allCsvPayloads(now()->subDays(30), now()->addDay(), $owner->id);

        $this->assertStringStartsWith('crm_pipeline_report_', $pipelineCsv['filename']);
        $this->assertNotEmpty($pipelineCsv['rows']);
        $this->assertCount(4, $pipelineCsv['rows'][0]);
        $this->assertSame(__('crm.reports.tables.stage'), $pipelineCsv['headers'][0]);

        $ownerRow = collect($ownerCsv['rows'])->first(
            fn (array $row): bool => $row[0] === $owner->name
        );

        $this->assertNotNull($ownerRow);
        $this->assertSame('1', $ownerRow[1]);
        $this->assertSame('1', $ownerRow[2]);
        $this->assertSame('7000.00', $ownerRow[3]);
        $this->assertSame('1', $ownerRow[4]);

        $this->assertStringStartsWith('crm_activity_report_', $activityCsv['filename']);
        $this->assertCount(5, $activityCsv['rows']);
        $this->assertSame(__('crm.reports.csv.metric'), $activityCsv['headers'][0]);
        $this->assertSame(__('crm.reports.metrics.completion_rate'), $activityCsv['rows'][4][0]);

        $this->assertStringStartsWith('crm_source_performance_', $sourceCsv['filename']);
        $this->assertSame(__('crm.reports.tables.source'), $sourceCsv['headers'][0]);
        $this->assertSame(__('crm.reports.tables.conversion_rate'), $sourceCsv['headers'][5]);
        $this->assertNotEmpty($sourceCsv['rows']);

        $this->assertSame(
            ['conversion', 'pipeline', 'owner_performance', 'activity', 'source_performance'],
            array_keys($allCsv),
        );
        $this->assertStringStartsWith('crm_conversion_report_', $allCsv['conversion']['filename']);
        $this->assertStringStartsWith('crm_source_performance_', $allCsv['source_performance']['filename']);

        Carbon::setTestNow();
    }
}
