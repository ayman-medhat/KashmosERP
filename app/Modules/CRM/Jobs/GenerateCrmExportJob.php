<?php

namespace App\Modules\CRM\Jobs;

use App\Modules\CRM\Models\CrmExportRequest;
use App\Modules\CRM\Services\CrmReportExportService;
use Carbon\Carbon;
use Filament\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateCrmExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public CrmExportRequest $exportRequest,
    ) {
    }

    public function handle(CrmReportExportService $exportService): void
    {
        $this->exportRequest->update(['status' => 'processing']);

        try {
            $filters = $this->exportRequest->filters;
            $dateFrom = filled($filters['date_from'] ?? null) ? Carbon::parse($filters['date_from']) : null;
            $dateTo = filled($filters['date_to'] ?? null) ? Carbon::parse($filters['date_to']) : null;
            $ownerId = $filters['owner_id'] ?? null;
            $sourceId = $filters['source_id'] ?? null;

            $payload = match ($this->exportRequest->type) {
                'conversion' => $exportService->conversionCsv($dateFrom, $dateTo, $ownerId, $sourceId),
                'pipeline' => $exportService->pipelineCsv($ownerId),
                'owner_performance' => $exportService->ownerPerformanceCsv($dateFrom, $dateTo),
                'activity' => $exportService->activityCsv($dateFrom, $dateTo, $ownerId),
                'source_performance' => $exportService->sourcePerformanceCsv($dateFrom, $dateTo, $ownerId),
                default => throw new \Exception("Unknown export type: {$this->exportRequest->type}"),
            };

            $extension = $this->exportRequest->format === 'xlsx' ? 'xlsx' : 'csv';
            $fileName = "exports/crm/{$this->exportRequest->uuid}.{$extension}";

            if (!Storage::disk('public')->exists('exports/crm')) {
                Storage::disk('public')->makeDirectory('exports/crm');
            }

            $fullPath = Storage::disk('public')->path($fileName);
            $directory = dirname($fullPath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            $exportService->writeToFile($payload, $fullPath, $this->exportRequest->format);

            $this->exportRequest->update([
                'status' => 'completed',
                'file_path' => $fileName,
                'completed_at' => now(),
            ]);

            $this->notifyUser(true);
        } catch (Throwable $e) {
            $this->exportRequest->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            $this->notifyUser(false);
            throw $e;
        }
    }

    private function notifyUser(bool $success): void
    {
        $notification = Notification::make()
            ->title($success ? __('crm.reports.notifications.export_ready_title') : __('crm.reports.notifications.export_failed_title'))
            ->body($success ? __('crm.reports.notifications.export_ready_body', ['type' => $this->exportRequest->type]) : __('crm.reports.notifications.export_failed_body'))
            ->icon($success ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
            ->iconColor($success ? 'success' : 'danger');

        if ($success) {
            $notification->actions([
                NotificationAction::make('download')
                    ->label(__('crm.reports.notifications.download_action'))
                    ->url(Storage::disk('public')->url($this->exportRequest->file_path), shouldOpenInNewTab: true),
            ]);
        }

        $notification->sendToDatabase($this->exportRequest->user);
    }
}
