<x-filament-panels::page>
    @php($isRtl = app()->getLocale() === 'ar')

    <div class="space-y-4" @if($isRtl) dir="rtl" @endif>
        @if (blank($timelineEntries))
            <div class="rounded-xl border border-gray-200 bg-white p-6 text-sm text-gray-600 shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                {{ __('crm.pages.timeline.empty') }}
            </div>
        @else
            @foreach ($timelineEntries as $entry)
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                {{ $entry['type'] }}
                            </span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                {{ $entry['title'] }}
                            </span>
                        </div>

                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $entry['occurred_at'] }}
                        </span>
                    </div>

                    <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">
                        {{ $entry['description'] }}
                    </p>

                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        {{ __('crm.pages.timeline.by') }}: {{ $entry['actor'] }}
                    </p>
                </div>
            @endforeach
        @endif
    </div>
</x-filament-panels::page>
