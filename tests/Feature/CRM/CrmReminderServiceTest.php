<?php

namespace Tests\Feature\CRM;

use App\Core\Services\SettingsService;
use App\Core\Models\User;
use App\Modules\CRM\Models\CrmActivity;
use App\Modules\CRM\Models\CrmLead;
use App\Modules\CRM\Models\CrmOpportunity;
use App\Modules\CRM\Models\CrmTask;
use App\Modules\CRM\Notifications\CrmReminderDigestNotification;
use App\Modules\CRM\Services\CrmReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class CrmReminderServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_process_marks_overdue_activities_and_sends_owner_digest(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 2, 9, 0, 0, 'UTC'));
        $this->seed();
        Notification::fake();
        Cache::flush();

        $owner = User::factory()->create([
            'email' => 'reminder-owner@kashmos.test',
            'locale' => 'en',
            'is_active' => true,
        ]);

        $lead = CrmLead::query()->where('status', 'new')->firstOrFail();
        $opportunity = CrmOpportunity::query()->where('status', 'open')->firstOrFail();

        CrmActivity::query()->create([
            'uuid' => (string) Str::uuid(),
            'subject_type' => CrmLead::class,
            'subject_id' => $lead->id,
            'title' => 'Overdue qualification call',
            'activity_type' => 'call',
            'status' => 'scheduled',
            'priority' => 'high',
            'due_at' => now()->subHour(),
            'owner_id' => $owner->id,
        ]);

        CrmTask::query()->create([
            'uuid' => (string) Str::uuid(),
            'subject_type' => CrmOpportunity::class,
            'subject_id' => $opportunity->id,
            'title' => 'Prepare revised proposal draft',
            'status' => 'open',
            'priority' => 'normal',
            'due_at' => now()->addHours(2),
            'owner_id' => $owner->id,
        ]);

        $lead->forceFill([
            'owner_id' => $owner->id,
            'status' => 'qualified',
            'next_follow_up_at' => now()->addHours(3),
        ])->save();

        $opportunity->forceFill([
            'owner_id' => $owner->id,
            'status' => 'open',
            'next_follow_up_at' => now()->subHours(2),
        ])->save();

        $result = app(CrmReminderService::class)->process(now(), 4);

        $this->assertSame(1, $result['overdue_activities_marked']);
        $this->assertSame(1, $result['owners_notified']);
        $this->assertDatabaseHas('crm_activities', [
            'title' => 'Overdue qualification call',
            'status' => 'overdue',
        ]);

        Notification::assertSentTimes(CrmReminderDigestNotification::class, 1);

        Carbon::setTestNow();
    }

    public function test_reminder_command_runs_successfully_and_dispatches_notifications(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 2, 9, 0, 0, 'UTC'));
        $this->seed();
        Notification::fake();
        Cache::flush();

        $owner = User::factory()->create([
            'email' => 'command-reminder-owner@kashmos.test',
            'locale' => 'ar',
            'is_active' => true,
        ]);

        $lead = CrmLead::query()->where('status', 'new')->firstOrFail();

        CrmActivity::query()->create([
            'uuid' => (string) Str::uuid(),
            'subject_type' => CrmLead::class,
            'subject_id' => $lead->id,
            'title' => 'Command reminder activity',
            'activity_type' => 'task',
            'status' => 'scheduled',
            'priority' => 'normal',
            'due_at' => now()->addHour(),
            'owner_id' => $owner->id,
        ]);

        $this->artisan('crm:send-reminders --hours=2')
            ->assertExitCode(0);

        Notification::assertSentTimes(CrmReminderDigestNotification::class, 1);

        Carbon::setTestNow();
    }

    public function test_reminder_command_uses_configured_hours_when_option_is_missing(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 2, 9, 0, 0, 'UTC'));
        $this->seed();
        Notification::fake();
        Cache::flush();

        app(SettingsService::class)->put('crm', 'reminder_look_ahead_hours', 1);

        $ownerA = User::factory()->create([
            'email' => 'configured-hours-owner-a@kashmos.test',
            'locale' => 'en',
            'is_active' => true,
        ]);
        $ownerB = User::factory()->create([
            'email' => 'configured-hours-owner-b@kashmos.test',
            'locale' => 'en',
            'is_active' => true,
        ]);

        $lead = CrmLead::query()->where('status', 'new')->firstOrFail();

        CrmActivity::query()->create([
            'uuid' => (string) Str::uuid(),
            'subject_type' => CrmLead::class,
            'subject_id' => $lead->id,
            'title' => 'In configured window',
            'activity_type' => 'call',
            'status' => 'scheduled',
            'priority' => 'normal',
            'due_at' => now()->addMinutes(30),
            'owner_id' => $ownerA->id,
        ]);

        CrmActivity::query()->create([
            'uuid' => (string) Str::uuid(),
            'subject_type' => CrmLead::class,
            'subject_id' => $lead->id,
            'title' => 'Outside configured window',
            'activity_type' => 'call',
            'status' => 'scheduled',
            'priority' => 'normal',
            'due_at' => now()->addHours(2),
            'owner_id' => $ownerB->id,
        ]);

        $this->artisan('crm:send-reminders')
            ->assertExitCode(0)
            ->expectsOutputToContain('notifications sent: 1');

        Notification::assertSentTimes(CrmReminderDigestNotification::class, 1);

        Carbon::setTestNow();
    }
}
