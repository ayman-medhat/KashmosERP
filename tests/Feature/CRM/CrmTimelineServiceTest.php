<?php

namespace Tests\Feature\CRM;

use App\Modules\CRM\Models\CrmLead;
use App\Modules\CRM\Models\CrmOpportunity;
use App\Modules\CRM\Services\CrmTimelineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmTimelineServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_opportunity_timeline_contains_activity_note_and_stage_entries(): void
    {
        $this->seed();

        $opportunity = CrmOpportunity::query()->where('opportunity_no', 'OPP-000001')->firstOrFail();
        $entries = app(CrmTimelineService::class)->forSubject(CrmOpportunity::class, $opportunity->id);
        $types = collect($entries)->pluck('type')->all();

        $this->assertContains('Activity', $types);
        $this->assertContains('Note', $types);
        $this->assertContains('Stage', $types);
    }

    public function test_timeline_entries_are_sorted_descending_by_occurrence_time(): void
    {
        $this->seed();

        $lead = CrmLead::query()->where('lead_no', 'LEAD-000002')->firstOrFail();
        $entries = app(CrmTimelineService::class)->forSubject(CrmLead::class, $lead->id);
        $timestamps = collect($entries)->pluck('occurred_at')->map(fn ($value) => $value->timestamp)->all();

        $sorted = $timestamps;
        rsort($sorted);

        $this->assertSame($sorted, $timestamps);
    }
}

