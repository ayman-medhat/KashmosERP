<x-filament-panels::page>
    @php($stageOptions = $this->stageOptions())
    @php($isRtl = app()->getLocale() === 'ar')

    <div class="grid gap-4 xl:grid-cols-5 lg:grid-cols-3 md:grid-cols-2" @if($isRtl) dir="rtl" @endif>
        @foreach ($this->getStages() as $stage)
            <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <header class="mb-3 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                        {{ $stage->name }}
                    </h3>
                    <span class="rounded-md px-2 py-1 text-xs font-medium text-white" style="background-color: {{ $stage->color }}">
                        {{ $stage->opportunities->count() }}
                    </span>
                </header>

                <div class="space-y-3">
                    @forelse ($stage->opportunities as $opportunity)
                        <article class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800">
                            <div class="mb-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ $opportunity->opportunity_no }}
                            </div>

                            <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                {{ $opportunity->name }}
                            </div>

                            <div class="mt-2 text-xs text-gray-600 dark:text-gray-300">
                                {{ __('crm.pages.pipeline_board.owner') }}: {{ $opportunity->owner?->name ?? __('crm.common.unassigned') }}
                            </div>
                            <div class="text-xs text-gray-600 dark:text-gray-300">
                                {{ __('crm.pages.pipeline_board.value') }}: {{ number_format((float) $opportunity->expected_value, 2) }} EGP
                            </div>
                            <div class="text-xs text-gray-600 dark:text-gray-300">
                                {{ __('crm.pages.pipeline_board.probability') }}: {{ $opportunity->probability }}%
                            </div>

                            <div class="mt-3 flex gap-2">
                                <select
                                    class="w-full rounded-md border-gray-300 text-xs dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                    wire:model.live="targetStages.{{ $opportunity->id }}"
                                >
                                    @foreach ($stageOptions as $optionId => $optionLabel)
                                        <option value="{{ $optionId }}">{{ $optionLabel }}</option>
                                    @endforeach
                                </select>

                                <button
                                    type="button"
                                    class="rounded-md bg-primary-600 px-3 py-1 text-xs font-medium text-white hover:bg-primary-500"
                                    wire:click="moveOpportunity({{ $opportunity->id }})"
                                >
                                    {{ __('crm.pages.pipeline_board.move') }}
                                </button>
                            </div>
                        </article>
                    @empty
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ __('crm.pages.pipeline_board.empty_stage') }}
                        </p>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>
</x-filament-panels::page>
