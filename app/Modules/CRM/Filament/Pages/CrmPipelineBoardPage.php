<?php

namespace App\Modules\CRM\Filament\Pages;

use App\Modules\CRM\Models\CrmOpportunity;
use App\Modules\CRM\Models\CrmPipelineStage;
use App\Modules\CRM\Services\CrmOpportunityService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class CrmPipelineBoardPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-view-columns';

    protected static ?int $navigationSort = 11;

    protected string $view = 'filament.pages.crm-pipeline-board-page';

    public array $targetStages = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('crm.view') ?? false;
    }

    public static function getNavigationGroup(): ?string
    {
        return __('core.navigation.crm');
    }

    public static function getNavigationLabel(): string
    {
        return __('crm.pages.pipeline_board.navigation');
    }

    public function getTitle(): string
    {
        return __('crm.pages.pipeline_board.title');
    }

    /**
     * @return \Illuminate\Support\Collection<int, CrmPipelineStage>
     */
    public function getStages()
    {
        $stages = CrmPipelineStage::query()
            ->where('is_active', true)
            ->orderBy('stage_order')
            ->with(['opportunities' => function ($query): void {
                $query
                    ->with('owner')
                    ->orderByRaw('expected_close_date IS NULL')
                    ->orderBy('expected_close_date');
            }])
            ->get();

        foreach ($stages as $stage) {
            foreach ($stage->opportunities as $opportunity) {
                $this->targetStages[$opportunity->id] ??= $stage->id;
            }
        }

        return $stages;
    }

    /**
     * @return array<int, string>
     */
    public function stageOptions(): array
    {
        return CrmPipelineStage::query()
            ->where('is_active', true)
            ->orderBy('stage_order')
            ->get()
            ->pluck('name', 'id')
            ->all();
    }

    public function moveOpportunity(int $opportunityId): void
    {
        $opportunity = CrmOpportunity::query()->findOrFail($opportunityId);
        abort_unless(auth()->user()?->can('moveStage', $opportunity) ?? false, 403);

        $targetStageId = (int) ($this->targetStages[$opportunityId] ?? 0);
        $stage = CrmPipelineStage::query()->where('is_active', true)->findOrFail($targetStageId);

        app(CrmOpportunityService::class)->moveStage(
            opportunity: $opportunity,
            stage: $stage,
            note: __('crm.pages.pipeline_board.move_note'),
        );

        Notification::make()
            ->title(__('crm.pages.pipeline_board.stage_updated'))
            ->success()
            ->send();
    }
}
