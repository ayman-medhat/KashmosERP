<?php

namespace App\Modules\CRM\Filament\Pages;

use App\Models\User;
use App\Modules\CRM\Jobs\GenerateCrmExportJob;
use App\Modules\CRM\Models\CrmExportRequest;
use App\Modules\CRM\Models\CrmLeadSource;
use App\Modules\CRM\Models\CrmReportPreset;
use App\Modules\CRM\Services\CrmReportExportService;
use App\Modules\CRM\Services\CrmReportsService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class CrmReportsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?int $navigationSort = 13;

    protected string $view = 'filament.pages.crm-reports-page';

    public ?array $data = [];

    /**
     * @var array{
     *     total_leads: int,
     *     qualified_leads: int,
     *     converted_leads: int,
     *     disqualified_leads: int,
     *     conversion_rate: float
     * }
     */
    public array $conversionSummary = [];

    /**
     * @var array{
     *     total_activities: int,
     *     completed_activities: int,
     *     overdue_activities: int,
     *     total_tasks: int,
     *     completed_tasks: int,
     *     overdue_tasks: int,
     *     completion_rate: float
     * }
     */
    public array $activitySummary = [];

    /**
     * @var Collection<int, array{
     *     stage: string,
     *     opportunity_count: int,
     *     open_value: float,
     *     weighted_value: float
     * }>
     */
    public Collection $pipelineRows;

    /**
     * @var Collection<int, array{
     *     owner: string,
     *     open_opportunities: int,
     *     won_opportunities: int,
     *     won_value: float,
     *     completed_activities: int
     * }>
     */
    public Collection $ownerRows;

    public function mount(CrmReportsService $reports): void
    {
        abort_unless(static::canAccess(), 403);

        $this->pipelineRows = collect();
        $this->ownerRows = collect();

        $this->form->fill([
            'date_from' => now()->subDays(30)->toDateString(),
            'date_to' => now()->toDateString(),
            'owner_id' => null,
            'source_id' => null,
        ]);

        $this->refreshReports($reports);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('crm.view_reports') ?? false;
    }

    public static function getNavigationGroup(): ?string
    {
        return __('core.navigation.crm');
    }

    public static function getNavigationLabel(): string
    {
        return __('crm.reports.navigation_label');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make(__('crm.reports.filters.section'))
                    ->schema([
                        DatePicker::make('date_from')
                            ->label(__('crm.reports.filters.date_from')),
                        DatePicker::make('date_to')
                            ->label(__('crm.reports.filters.date_to')),
                        Select::make('owner_id')
                            ->label(__('crm.reports.filters.owner'))
                            ->options(User::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload(),
                        Select::make('source_id')
                            ->label(__('crm.reports.filters.source'))
                            ->options(CrmLeadSource::query()->where('is_active', true)->orderBy('id')->get()->pluck('name', 'id'))
                            ->searchable()
                            ->preload(),
                        Select::make('preset_id')
                            ->label(__('crm.reports.filters.preset'))
                            ->options(fn() => CrmReportPreset::query()->where('user_id', auth()->id())->pluck('name', 'id'))
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(fn($state) => $this->loadPreset($state)),
                    ])
                    ->columns(5),
            ]);
    }

    public function loadPreset(?int $presetId): void
    {
        if (!$presetId) {
            return;
        }

        $preset = CrmReportPreset::query()->where('user_id', auth()->id())->find($presetId);

        if (!$preset) {
            return;
        }

        $this->form->fill([
            ...$this->data,
            ...$preset->filters,
        ]);

        Notification::make()
            ->title(__('crm.reports.filters.preset_loaded'))
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save_preset')
                ->label(__('crm.reports.filters.save_preset'))
                ->icon('heroicon-o-bookmark')
                ->form([
                    TextInput::make('name')
                        ->label(__('crm.reports.filters.preset_name'))
                        ->required(),
                ])
                ->action(function (array $data) {
                    CrmReportPreset::create([
                        'user_id' => auth()->id(),
                        'name' => $data['name'],
                        'type' => 'crm_reports', // generic for this page
                        'filters' => $this->data,
                    ]);

                    Notification::make()
                        ->title(__('crm.reports.filters.preset_saved'))
                        ->success()
                        ->send();
                }),
            Action::make('export_xlsx')
                ->label(__('crm.reports.actions.export_xlsx_queued'))
                ->icon('heroicon-o-table-cells')
                ->form([
                    Select::make('export_type')
                        ->label(__('crm.reports.exports.type'))
                        ->options([
                            'conversion' => __('crm.reports.sections.pipeline_report'),
                            'pipeline' => __('crm.reports.sections.pipeline_report'), // wait, these labels might overlap
                            'owner_performance' => __('crm.reports.sections.owner_performance'),
                            'activity' => __('crm.reports.sections.activity_report'),
                            'source_performance' => __('crm.reports.actions.export_source_csv'),
                        ])
                        ->required(),
                ])
                ->action(function (array $data) {
                    $exportRequest = CrmExportRequest::create([
                        'user_id' => auth()->id(),
                        'type' => $data['export_type'],
                        'format' => 'xlsx',
                        'status' => 'pending',
                        'filters' => $this->reportFilters(),
                    ]);

                    GenerateCrmExportJob::dispatch($exportRequest);

                    Notification::make()
                        ->title(__('crm.reports.notifications.export_ready_title'))
                        ->body(__('crm.reports.notifications.export_ready_body', ['type' => $data['export_type']]))
                        ->info()
                        ->send();
                }),
            Action::make('apply_filters')
                ->label(__('crm.reports.actions.apply_filters'))
                ->icon('heroicon-o-funnel')
                ->submit('applyFilters'),
            Action::make('export_conversion_csv')
                ->label(__('crm.reports.actions.export_conversion_csv'))
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(fn(): bool => auth()->user()?->can('crm.export') ?? false)
                ->action(fn(): StreamedResponse => $this->exportConversionCsv()),
            Action::make('export_pipeline_csv')
                ->label(__('crm.reports.actions.export_pipeline_csv'))
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(fn(): bool => auth()->user()?->can('crm.export') ?? false)
                ->action(fn(): StreamedResponse => $this->exportPipelineCsv()),
            Action::make('export_owner_csv')
                ->label(__('crm.reports.actions.export_owner_csv'))
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(fn(): bool => auth()->user()?->can('crm.export') ?? false)
                ->action(fn(): StreamedResponse => $this->exportOwnerPerformanceCsv()),
            Action::make('export_activity_csv')
                ->label(__('crm.reports.actions.export_activity_csv'))
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(fn(): bool => auth()->user()?->can('crm.export') ?? false)
                ->action(fn(): StreamedResponse => $this->exportActivityCsv()),
            Action::make('export_source_csv')
                ->label(__('crm.reports.actions.export_source_csv'))
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(fn(): bool => auth()->user()?->can('crm.export') ?? false)
                ->action(fn(): StreamedResponse => $this->exportSourceCsv()),
            Action::make('export_all_csv_zip')
                ->label(__('crm.reports.actions.export_all_csv_zip'))
                ->icon('heroicon-o-archive-box-arrow-down')
                ->visible(fn(): bool => auth()->user()?->can('crm.export') ?? false)
                ->action(fn(): BinaryFileResponse => $this->exportAllCsvZip()),
        ];
    }

    public function applyFilters(CrmReportsService $reports): void
    {
        $this->refreshReports($reports);
    }

    public function exportConversionCsv(): StreamedResponse
    {
        $filters = $this->reportFilters();

        return $this->streamCsv(
            app(CrmReportExportService::class)->conversionCsv(
                dateFrom: $filters['date_from'],
                dateTo: $filters['date_to'],
                ownerId: $filters['owner_id'],
                sourceId: $filters['source_id'],
            ),
        );
    }

    public function exportPipelineCsv(): StreamedResponse
    {
        $filters = $this->reportFilters();

        return $this->streamCsv(
            app(CrmReportExportService::class)->pipelineCsv(
                ownerId: $filters['owner_id'],
            ),
        );
    }

    public function exportOwnerPerformanceCsv(): StreamedResponse
    {
        $filters = $this->reportFilters();

        return $this->streamCsv(
            app(CrmReportExportService::class)->ownerPerformanceCsv(
                dateFrom: $filters['date_from'],
                dateTo: $filters['date_to'],
            ),
        );
    }

    public function exportActivityCsv(): StreamedResponse
    {
        $filters = $this->reportFilters();

        return $this->streamCsv(
            app(CrmReportExportService::class)->activityCsv(
                dateFrom: $filters['date_from'],
                dateTo: $filters['date_to'],
                ownerId: $filters['owner_id'],
            ),
        );
    }

    public function exportSourceCsv(): StreamedResponse
    {
        $filters = $this->reportFilters();

        return $this->streamCsv(
            app(CrmReportExportService::class)->sourcePerformanceCsv(
                dateFrom: $filters['date_from'],
                dateTo: $filters['date_to'],
                ownerId: $filters['owner_id'],
            ),
        );
    }

    public function exportAllCsvZip(): BinaryFileResponse
    {
        $filters = $this->reportFilters();

        $payloads = app(CrmReportExportService::class)->allCsvPayloads(
            dateFrom: $filters['date_from'],
            dateTo: $filters['date_to'],
            ownerId: $filters['owner_id'],
            sourceId: $filters['source_id'],
        );

        return $this->streamZip($payloads, 'crm_reports_' . now()->format('Ymd_His') . '.zip');
    }

    private function refreshReports(CrmReportsService $reports): void
    {
        $filters = $this->reportFilters();

        $this->conversionSummary = $reports->conversionSummary(
            $filters['date_from'],
            $filters['date_to'],
            $filters['owner_id'],
            $filters['source_id'],
        );
        $this->pipelineRows = $reports->pipelineByStage($filters['owner_id']);
        $this->activitySummary = $reports->activitySummary($filters['date_from'], $filters['date_to'], $filters['owner_id']);
        $this->ownerRows = $reports->ownerPerformance($filters['date_from'], $filters['date_to']);
    }

    /**
     * @return array{
     *     date_from: ?Carbon,
     *     date_to: ?Carbon,
     *     owner_id: ?int,
     *     source_id: ?int
     * }
     */
    private function reportFilters(): array
    {
        $state = $this->form->getState();

        return [
            'date_from' => filled($state['date_from'] ?? null) ? Carbon::parse($state['date_from'])->startOfDay() : null,
            'date_to' => filled($state['date_to'] ?? null) ? Carbon::parse($state['date_to'])->endOfDay() : null,
            'owner_id' => filled($state['owner_id'] ?? null) ? (int) $state['owner_id'] : null,
            'source_id' => filled($state['source_id'] ?? null) ? (int) $state['source_id'] : null,
        ];
    }

    /**
     * @param array{filename: string, headers: array<int, string>, rows: array<int, array<int, string>>} $payload
     */
    private function streamCsv(array $payload): StreamedResponse
    {
        return response()->streamDownload(function () use ($payload): void {
            $handle = fopen('php://output', 'wb');
            if ($handle === false) {
                return;
            }

            // UTF-8 BOM improves Arabic rendering in spreadsheet apps.
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $payload['headers']);

            foreach ($payload['rows'] as $row) {
                fputcsv($handle, array_map(static fn(string $value): string => $value, $row));
            }

            fclose($handle);
        }, $payload['filename'], [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param array<string, array{filename: string, headers: array<int, string>, rows: array<int, array<int, string>>}> $files
     */
    private function streamZip(array $files, string $filename): BinaryFileResponse
    {
        abort_unless(class_exists(ZipArchive::class), 500);

        $temporaryPath = tempnam(sys_get_temp_dir(), 'kashmos_crm_reports_');
        abort_unless($temporaryPath !== false, 500);

        $zip = new ZipArchive();
        $openResult = $zip->open($temporaryPath, ZipArchive::OVERWRITE);
        abort_unless($openResult === true, 500);

        foreach ($files as $payload) {
            $zip->addFromString($payload['filename'], $this->csvString($payload));
        }

        $zip->close();

        return response()->download(
            $temporaryPath,
            $filename,
            ['Content-Type' => 'application/zip']
        )->deleteFileAfterSend(true);
    }

    /**
     * @param array{filename: string, headers: array<int, string>, rows: array<int, array<int, string>>} $payload
     */
    private function csvString(array $payload): string
    {
        $handle = fopen('php://temp', 'w+b');
        if ($handle === false) {
            return '';
        }

        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, $payload['headers']);

        foreach ($payload['rows'] as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return is_string($csv) ? $csv : '';
    }
}
