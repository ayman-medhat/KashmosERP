<x-filament-panels::page>
    @php($isRtl = app()->getLocale() === 'ar')

    <form wire:submit="applyFilters">
        {{ $this->form }}
    </form>

    <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-5" @if($isRtl) dir="rtl" @endif>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('crm.reports.metrics.total_leads') }}</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                {{ $conversionSummary['total_leads'] ?? 0 }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('crm.reports.metrics.qualified_leads') }}</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                {{ $conversionSummary['qualified_leads'] ?? 0 }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('crm.reports.metrics.converted_leads') }}</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                {{ $conversionSummary['converted_leads'] ?? 0 }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('crm.reports.metrics.disqualified_leads') }}
            </div>
            <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                {{ $conversionSummary['disqualified_leads'] ?? 0 }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('crm.reports.metrics.conversion_rate') }}</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                {{ number_format((float) ($conversionSummary['conversion_rate'] ?? 0), 2) }}%</div>
        </div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                {{ __('crm.reports.sections.pipeline_report') }}</h3>
            <div class="mt-3 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-start text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            <th class="px-2 py-2">{{ __('crm.reports.tables.stage') }}</th>
                            <th class="px-2 py-2">{{ __('crm.reports.tables.count') }}</th>
                            <th class="px-2 py-2">{{ __('crm.reports.tables.open_value') }}</th>
                            <th class="px-2 py-2">{{ __('crm.reports.tables.weighted_value') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pipelineRows as $row)
                            <tr class="border-t border-gray-100 dark:border-gray-800">
                                <td class="px-2 py-2">{{ $row['stage'] }}</td>
                                <td class="px-2 py-2">{{ $row['opportunity_count'] }}</td>
                                <td class="px-2 py-2">{{ number_format((float) $row['open_value'], 2) }}</td>
                                <td class="px-2 py-2">{{ number_format((float) $row['weighted_value'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-2 py-3 text-gray-500 dark:text-gray-400">
                                    {{ __('crm.reports.empty.pipeline') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                {{ __('crm.reports.sections.activity_report') }}</h3>
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        {{ __('crm.reports.metrics.activities_completed_total') }}</div>
                    <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">
                        {{ $activitySummary['completed_activities'] ?? 0 }} /
                        {{ $activitySummary['total_activities'] ?? 0 }}
                    </div>
                </div>
                <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        {{ __('crm.reports.metrics.tasks_completed_total') }}</div>
                    <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">
                        {{ $activitySummary['completed_tasks'] ?? 0 }} / {{ $activitySummary['total_tasks'] ?? 0 }}
                    </div>
                </div>
                <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        {{ __('crm.reports.metrics.overdue_activities') }}</div>
                    <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">
                        {{ $activitySummary['overdue_activities'] ?? 0 }}
                    </div>
                </div>
                <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('crm.reports.metrics.overdue_tasks') }}
                    </div>
                    <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">
                        {{ $activitySummary['overdue_tasks'] ?? 0 }}
                    </div>
                </div>
            </div>
            <div class="mt-3 rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('crm.reports.metrics.completion_rate') }}
                </div>
                <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">
                    {{ number_format((float) ($activitySummary['completion_rate'] ?? 0), 2) }}%
                </div>
            </div>
        </section>
    </div>

    <section
        class="mt-6 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
            {{ __('crm.reports.sections.owner_performance') }}</h3>
        <div class="mt-3 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-start text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        <th class="px-2 py-2">{{ __('crm.reports.tables.owner') }}</th>
                        <th class="px-2 py-2">{{ __('crm.reports.tables.open_opportunities') }}</th>
                        <th class="px-2 py-2">{{ __('crm.reports.tables.won_opportunities') }}</th>
                        <th class="px-2 py-2">{{ __('crm.reports.tables.won_value') }}</th>
                        <th class="px-2 py-2">{{ __('crm.reports.tables.completed_activities') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ownerRows as $row)
                        <tr class="border-t border-gray-100 dark:border-gray-800">
                            <td class="px-2 py-2">{{ $row['owner'] }}</td>
                            <td class="px-2 py-2">{{ $row['open_opportunities'] }}</td>
                            <td class="px-2 py-2">{{ $row['won_opportunities'] }}</td>
                            <td class="px-2 py-2">{{ number_format((float) $row['won_value'], 2) }}</td>
                            <td class="px-2 py-2">{{ $row['completed_activities'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-2 py-3 text-gray-500 dark:text-gray-400">
                                {{ __('crm.reports.empty.owner_performance') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section
        class="mt-6 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('crm.reports.exports.title') }}</h3>
        <div class="mt-3 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-start text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        <th class="px-2 py-2">{{ __('crm.reports.exports.type') }}</th>
                        <th class="px-2 py-2">{{ __('crm.reports.exports.format') }}</th>
                        <th class="px-2 py-2">{{ __('crm.reports.exports.status') }}</th>
                        <th class="px-2 py-2">{{ __('crm.reports.exports.created_at') }}</th>
                        <th class="px-2 py-2">{{ __('crm.reports.exports.download') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @php($recentExports = \App\Modules\CRM\Models\CrmExportRequest::query()->where('user_id', auth()->id())->latest()->take(5)->get())
                    @forelse($recentExports as $export)
                        <tr class="border-t border-gray-100 dark:border-gray-800">
                            <td class="px-2 py-2">{{ $export->type }}</td>
                            <td class="px-2 py-2">{{ strtoupper($export->format) }}</td>
                            <td class="px-2 py-2">
                                <span
                                    class="rounded-full px-2 py-1 text-xs @if($export->status === 'completed') bg-green-100 text-green-700 @elseif($export->status === 'failed') bg-red-100 text-red-700 @else bg-blue-100 text-blue-700 @endif">
                                    {{ $export->status }}
                                </span>
                            </td>
                            <td class="px-2 py-2">{{ $export->created_at->diffForHumans() }}</td>
                            <td class="px-2 py-2">
                                @if($export->status === 'completed' && $export->file_path)
                                    <a href="{{ Storage::disk('public')->url($export->file_path) }}" target="_blank"
                                        class="text-primary-600 hover:text-primary-500 dark:text-primary-400">
                                        {{ __('crm.reports.exports.download') }}
                                    </a>
                                @elseif($export->status === 'failed')
                                    <span class="text-xs text-red-500" title="{{ $export->error_message }}">Error</span>
                                @else
                                    <span class="text-xs text-gray-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-2 py-3 text-gray-500 dark:text-gray-400">
                                {{ __('crm.reports.exports.no_exports') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-filament-panels::page>