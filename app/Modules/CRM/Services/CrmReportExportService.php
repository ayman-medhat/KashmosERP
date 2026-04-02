<?php

namespace App\Modules\CRM\Services;

use App\Modules\CRM\Models\CrmLeadSource;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer as XLSXWriter;
use OpenSpout\Writer\CSV\Writer as CSVWriter;

class CrmReportExportService
{
    public function __construct(
        private readonly CrmReportsService $reports,
    ) {
    }

    /**
     * @return array{filename: string, headers: array<int, string>, rows: array<int, array<int, string>>}
     */
    public function conversionCsv(?Carbon $dateFrom = null, ?Carbon $dateTo = null, ?int $ownerId = null, ?int $sourceId = null): array
    {
        $summary = $this->reports->conversionSummary($dateFrom, $dateTo, $ownerId, $sourceId);

        return [
            'filename' => $this->filename('crm_conversion_report'),
            'headers' => [
                __('crm.reports.csv.metric'),
                __('crm.reports.csv.value'),
            ],
            'rows' => [
                [__('crm.reports.metrics.total_leads'), (string) $summary['total_leads']],
                [__('crm.reports.metrics.qualified_leads'), (string) $summary['qualified_leads']],
                [__('crm.reports.metrics.converted_leads'), (string) $summary['converted_leads']],
                [__('crm.reports.metrics.disqualified_leads'), (string) $summary['disqualified_leads']],
                [__('crm.reports.metrics.conversion_rate'), $this->asPercent((float) $summary['conversion_rate'])],
            ],
        ];
    }

    /**
     * @return array{filename: string, headers: array<int, string>, rows: array<int, array<int, string>>}
     */
    public function pipelineCsv(?int $ownerId = null): array
    {
        $rows = $this->reports
            ->pipelineByStage($ownerId)
            ->map(fn(array $row): array => [
                $row['stage'],
                (string) $row['opportunity_count'],
                $this->asNumber((float) $row['open_value']),
                $this->asNumber((float) $row['weighted_value']),
            ])
            ->values()
            ->all();

        return [
            'filename' => $this->filename('crm_pipeline_report'),
            'headers' => [
                __('crm.reports.tables.stage'),
                __('crm.reports.tables.count'),
                __('crm.reports.tables.open_value'),
                __('crm.reports.tables.weighted_value'),
            ],
            'rows' => $rows,
        ];
    }

    /**
     * @return array{filename: string, headers: array<int, string>, rows: array<int, array<int, string>>}
     */
    public function ownerPerformanceCsv(?Carbon $dateFrom = null, ?Carbon $dateTo = null): array
    {
        $rows = $this->reports
            ->ownerPerformance($dateFrom, $dateTo)
            ->map(fn(array $row): array => [
                $row['owner'],
                (string) $row['open_opportunities'],
                (string) $row['won_opportunities'],
                $this->asNumber((float) $row['won_value']),
                (string) $row['completed_activities'],
            ])
            ->values()
            ->all();

        return [
            'filename' => $this->filename('crm_owner_performance'),
            'headers' => [
                __('crm.reports.tables.owner'),
                __('crm.reports.tables.open_opportunities'),
                __('crm.reports.tables.won_opportunities'),
                __('crm.reports.tables.won_value'),
                __('crm.reports.tables.completed_activities'),
            ],
            'rows' => $rows,
        ];
    }

    /**
     * @return array{filename: string, headers: array<int, string>, rows: array<int, array<int, string>>}
     */
    public function activityCsv(?Carbon $dateFrom = null, ?Carbon $dateTo = null, ?int $ownerId = null): array
    {
        $summary = $this->reports->activitySummary($dateFrom, $dateTo, $ownerId);

        return [
            'filename' => $this->filename('crm_activity_report'),
            'headers' => [
                __('crm.reports.csv.metric'),
                __('crm.reports.csv.value'),
            ],
            'rows' => [
                [__('crm.reports.metrics.activities_completed_total'), (string) $summary['completed_activities'] . ' / ' . (string) $summary['total_activities']],
                [__('crm.reports.metrics.tasks_completed_total'), (string) $summary['completed_tasks'] . ' / ' . (string) $summary['total_tasks']],
                [__('crm.reports.metrics.overdue_activities'), (string) $summary['overdue_activities']],
                [__('crm.reports.metrics.overdue_tasks'), (string) $summary['overdue_tasks']],
                [__('crm.reports.metrics.completion_rate'), $this->asPercent((float) $summary['completion_rate'])],
            ],
        ];
    }

    /**
     * @return array{filename: string, headers: array<int, string>, rows: array<int, array<int, string>>}
     */
    public function sourcePerformanceCsv(?Carbon $dateFrom = null, ?Carbon $dateTo = null, ?int $ownerId = null): array
    {
        /** @var Collection<int, object{crm_lead_source_id: ?int, total_leads: int, qualified_leads: int, converted_leads: int, disqualified_leads: int}> $rows */
        $rows = $this->reports
            ->leadsQuery($dateFrom, $dateTo, $ownerId)
            ->selectRaw('crm_lead_source_id, COUNT(*) as total_leads')
            ->selectRaw("SUM(CASE WHEN status = 'qualified' THEN 1 ELSE 0 END) as qualified_leads")
            ->selectRaw("SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as converted_leads")
            ->selectRaw("SUM(CASE WHEN status = 'disqualified' THEN 1 ELSE 0 END) as disqualified_leads")
            ->groupBy('crm_lead_source_id')
            ->orderByDesc('total_leads')
            ->get();

        $sourceNames = CrmLeadSource::query()
            ->whereIn('id', $rows->pluck('crm_lead_source_id')->filter()->all())
            ->get()
            ->mapWithKeys(fn(CrmLeadSource $source): array => [$source->id => $source->name]);

        return [
            'filename' => $this->filename('crm_source_performance'),
            'headers' => [
                __('crm.reports.tables.source'),
                __('crm.reports.tables.total_leads'),
                __('crm.reports.tables.qualified_leads'),
                __('crm.reports.tables.converted_leads'),
                __('crm.reports.tables.disqualified_leads'),
                __('crm.reports.tables.conversion_rate'),
            ],
            'rows' => $rows
                ->map(function (object $row) use ($sourceNames): array {
                    $totalLeads = (int) $row->total_leads;
                    $convertedLeads = (int) $row->converted_leads;
                    $conversionRate = $totalLeads > 0 ? round(($convertedLeads / $totalLeads) * 100, 2) : 0.0;

                    return [
                        (string) ($sourceNames[$row->crm_lead_source_id] ?? __('crm.common.not_available')),
                        (string) $totalLeads,
                        (string) ((int) $row->qualified_leads),
                        (string) $convertedLeads,
                        (string) ((int) $row->disqualified_leads),
                        $this->asPercent($conversionRate),
                    ];
                })
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, array{filename: string, headers: array<int, string>, rows: array<int, array<int, string>>}>
     */
    public function allCsvPayloads(?Carbon $dateFrom = null, ?Carbon $dateTo = null, ?int $ownerId = null, ?int $sourceId = null): array
    {
        return [
            'conversion' => $this->conversionCsv($dateFrom, $dateTo, $ownerId, $sourceId),
            'pipeline' => $this->pipelineCsv($ownerId),
            'owner_performance' => $this->ownerPerformanceCsv($dateFrom, $dateTo),
            'activity' => $this->activityCsv($dateFrom, $dateTo, $ownerId),
            'source_performance' => $this->sourcePerformanceCsv($dateFrom, $dateTo, $ownerId),
        ];
    }

    /**
     * @param array{filename: string, headers: array<int, string>, rows: array<int, array<int, string>>} $payload
     */
    public function writeToFile(array $payload, string $path, string $format = 'csv'): void
    {
        $writer = $format === 'xlsx' ? new XLSXWriter() : new CSVWriter();
        $writer->openToFile($path);

        // Add UTF-8 BOM for CSV if needed, but OpenSpout handles it better generally.
        // For XLSX, it's a binary format.

        $writer->addRow(Row::fromValues($payload['headers']));

        foreach ($payload['rows'] as $row) {
            $writer->addRow(Row::fromValues($row));
        }

        $writer->close();
    }

    private function filename(string $prefix): string
    {
        return $prefix . '_' . now()->format('Ymd_His') . '.csv';
    }

    private function asNumber(float $value): string
    {
        return number_format($value, 2, '.', '');
    }

    private function asPercent(float $value): string
    {
        return $this->asNumber($value) . '%';
    }
}
