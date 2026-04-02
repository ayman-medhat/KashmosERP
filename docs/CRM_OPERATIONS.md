# Kashmos CRM Operations

This document covers the operational controls for CRM reminders and reports in Kashmos ERP.

## CRM Reminder Command

Run reminders manually:

```bash
./vendor/bin/sail artisan crm:send-reminders
```

Override look-ahead window (hours):

```bash
./vendor/bin/sail artisan crm:send-reminders --hours=6
```

The command marks overdue scheduled activities as `overdue`, aggregates due work by owner, and sends digest email reminders.

## Reminder Scheduler

Scheduler registration is defined in `routes/console.php`:

- Executes every 15 minutes
- Uses overlap protection
- Respects runtime CRM settings

Run scheduler worker in production:

```bash
php artisan schedule:work
```

For cron-based environments:

```cron
* * * * * php /path-to-project/artisan schedule:run >> /dev/null 2>&1
```

## CRM Reminder Settings

These keys are seeded in the `crm` settings group:

- `crm.reminders_enabled` (bool): Enables/disables scheduled reminder dispatch.
- `crm.reminder_look_ahead_hours` (int): Default look-ahead window when `--hours` is not provided.

## CRM Reports Export

The CRM Reports page (`/admin/crm-reports-page`) provides CSV exports for:

- Conversion summary
- Pipeline report
- Owner performance

Exports are guarded by `crm.export` permission and generated through `CrmReportExportService` to keep page logic thin.

## Validation Checklist

- Run CRM tests:

```bash
./vendor/bin/sail artisan test --testsuite=Feature --filter=CRM
```

- Run full suite:

```bash
./vendor/bin/sail artisan test
```
