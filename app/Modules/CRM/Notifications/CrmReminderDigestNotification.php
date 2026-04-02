<?php

namespace App\Modules\CRM\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CrmReminderDigestNotification extends Notification
{
    /**
     * @param array<string, int|float> $metrics
     */
    public function __construct(
        private readonly array $metrics,
        private readonly int $lookAheadHours,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $dashboardUrl = url('/admin/crm-dashboard-page');

        return (new MailMessage)
            ->subject(__('crm.notifications.reminder_subject'))
            ->greeting(__('crm.notifications.greeting', ['name' => $notifiable->name ?? __('crm.notifications.generic_user')]))
            ->line(__('crm.notifications.reminder_intro'))
            ->line(__('crm.notifications.total_due_items', ['count' => (int) ($this->metrics['total_due_items'] ?? 0)]))
            ->line(__('crm.notifications.overdue_activities', ['count' => (int) ($this->metrics['overdue_activities'] ?? 0)]))
            ->line(__('crm.notifications.upcoming_activities', [
                'count' => (int) ($this->metrics['upcoming_activities'] ?? 0),
                'hours' => $this->lookAheadHours,
            ]))
            ->line(__('crm.notifications.overdue_tasks', ['count' => (int) ($this->metrics['overdue_tasks'] ?? 0)]))
            ->line(__('crm.notifications.upcoming_tasks', [
                'count' => (int) ($this->metrics['upcoming_tasks'] ?? 0),
                'hours' => $this->lookAheadHours,
            ]))
            ->line(__('crm.notifications.overdue_lead_follow_ups', ['count' => (int) ($this->metrics['overdue_lead_follow_ups'] ?? 0)]))
            ->line(__('crm.notifications.upcoming_lead_follow_ups', [
                'count' => (int) ($this->metrics['upcoming_lead_follow_ups'] ?? 0),
                'hours' => $this->lookAheadHours,
            ]))
            ->line(__('crm.notifications.overdue_opportunity_follow_ups', ['count' => (int) ($this->metrics['overdue_opportunity_follow_ups'] ?? 0)]))
            ->line(__('crm.notifications.upcoming_opportunity_follow_ups', [
                'count' => (int) ($this->metrics['upcoming_opportunity_follow_ups'] ?? 0),
                'hours' => $this->lookAheadHours,
            ]))
            ->action(__('crm.notifications.open_crm_dashboard'), $dashboardUrl)
            ->line(__('crm.notifications.powered_by_kashmos'));
    }

    /**
     * @return array<string, int|float>
     */
    public function metrics(): array
    {
        return $this->metrics;
    }
}
