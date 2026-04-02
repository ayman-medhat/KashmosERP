<?php

namespace Tests\Feature\CRM;

use App\Modules\CRM\Models\CrmLead;
use App\Modules\CRM\Models\CrmTask;
use App\Modules\CRM\Services\CrmActivityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CrmActivityServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_and_task_completion_are_persisted(): void
    {
        $this->seed();

        $service = app(CrmActivityService::class);
        $activity = \App\Modules\CRM\Models\CrmActivity::query()
            ->where('status', 'scheduled')
            ->firstOrFail();

        $completedActivity = $service->completeActivity($activity);
        $this->assertSame('completed', $completedActivity->status);
        $this->assertNotNull($completedActivity->completed_at);

        $lead = CrmLead::query()->firstOrFail();
        $task = CrmTask::query()->create([
            'uuid' => (string) Str::uuid(),
            'subject_type' => CrmLead::class,
            'subject_id' => $lead->id,
            'title' => 'Prepare next follow-up brief',
            'status' => 'open',
            'priority' => 'normal',
        ]);

        $completedTask = $service->completeTask($task);
        $this->assertSame('completed', $completedTask->status);
        $this->assertNotNull($completedTask->completed_at);
    }
}

