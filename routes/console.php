<?php

use App\Core\Services\SettingsService;
use App\Modules\CRM\Services\CrmReminderService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('crm:send-reminders {--hours= : Reminder look-ahead window in hours}', function (CrmReminderService $service, SettingsService $settings): int {
    $configuredHours = 24;

    try {
        $configuredHours = (int) $settings->get('crm', 'reminder_look_ahead_hours', 24);
    } catch (\Throwable) {
        $configuredHours = 24;
    }

    $optionHours = $this->option('hours');
    $hours = is_numeric($optionHours) ? (int) $optionHours : $configuredHours;
    $hours = max(1, min(168, $hours));

    $result = $service->process(now(), $hours);

    $this->info(__('crm.commands.reminders_processed', [
        'owners' => $result['owners_with_due_items'],
        'notified' => $result['owners_notified'],
        'sent' => $result['notifications_sent'],
    ]));

    return self::SUCCESS;
})->purpose('Send CRM reminder notifications for due and overdue work');

Schedule::command('crm:send-reminders')
    ->everyFifteenMinutes()
    ->when(function (): bool {
        try {
            return (bool) app(SettingsService::class)->get('crm', 'reminders_enabled', true);
        } catch (\Throwable) {
            return true;
        }
    })
    ->withoutOverlapping();
