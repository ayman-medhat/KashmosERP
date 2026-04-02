<?php

namespace App\Modules\CRM\Filament\Pages;

use App\Modules\CRM\Services\CrmTimelineService;
use App\Modules\CRM\Support\CrmSubjectRegistry;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Model;

class CrmTimelinePage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.crm-timeline-page';

    public ?string $subjectType = null;

    public ?int $subjectId = null;

    public ?Model $subjectRecord = null;

    /**
     * @var array<int, array{
     *     type: string,
     *     title: string,
     *     description: string,
     *     actor: string,
     *     occurred_at: string
     * }>
     */
    public array $timelineEntries = [];

    public function mount(): void
    {
        $this->subjectType = request()->query('subject_type');
        $this->subjectId = (int) request()->query('subject_id');

        abort_unless($this->subjectType && $this->subjectId > 0, 404);
        abort_unless(CrmSubjectRegistry::isSupported($this->subjectType), 404);

        $modelClass = CrmSubjectRegistry::modelClass($this->subjectType);
        abort_unless($modelClass, 404);

        $record = $modelClass::query()->find($this->subjectId);
        abort_unless($record instanceof Model, 404);
        abort_unless(auth()->user()?->can('view', $record) ?? false, 403);

        $this->subjectRecord = $record;
        $this->timelineEntries = collect(app(CrmTimelineService::class)->forSubject(
            subjectType: $this->subjectType,
            subjectId: $this->subjectId,
        ))
            ->map(fn (array $entry): array => [
                ...$entry,
                'occurred_at' => $entry['occurred_at']->format('Y-m-d H:i'),
            ])
            ->all();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('crm.view') ?? false;
    }

    public function getHeading(): string
    {
        return __('crm.pages.timeline.heading');
    }

    public function getSubheading(): ?string
    {
        if (! $this->subjectRecord) {
            return null;
        }

        return CrmSubjectRegistry::typeLabel($this->subjectType)
            .' • '
            .CrmSubjectRegistry::recordLabel($this->subjectRecord);
    }
}
