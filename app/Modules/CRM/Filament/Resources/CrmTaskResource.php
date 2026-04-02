<?php

namespace App\Modules\CRM\Filament\Resources;

use App\Core\Models\User;
use App\Modules\CRM\Filament\Resources\CrmTaskResource\Pages;
use App\Modules\CRM\Models\CrmTask;
use App\Modules\CRM\Services\CrmActivityService;
use App\Modules\CRM\Support\CrmSubjectRegistry;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CrmTaskResource extends Resource
{
    protected static ?string $model = CrmTask::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-check-badge';

    protected static ?int $navigationSort = 8;

    public static function getNavigationGroup(): ?string
    {
        return __('core.navigation.crm');
    }

    public static function getNavigationLabel(): string
    {
        return __('crm.resources.task.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('crm.resources.task.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crm.resources.task.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('subject_type')
                    ->label(__('crm.resources.fields.subject_type'))
                    ->options(CrmSubjectRegistry::options())
                    ->live()
                    ->afterStateUpdated(fn (Set $set): mixed => $set('subject_id', null))
                    ->required(),
                Select::make('subject_id')
                    ->label(__('crm.resources.fields.subject'))
                    ->options(fn (Get $get): array => CrmSubjectRegistry::recordOptions($get('subject_type')))
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('crm_contact_id')
                    ->label(__('crm.resources.fields.contact'))
                    ->options(\App\Modules\CRM\Models\CrmContact::query()->orderBy('id')->get()->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload(),
                TextInput::make('title')->label(__('crm.resources.fields.title'))->required()->maxLength(255),
                Textarea::make('description')->label(__('crm.resources.fields.description'))->rows(4),
                Select::make('status')
                    ->label(__('crm.resources.fields.status'))
                    ->options([
                        'open' => __('crm.task_statuses.open'),
                        'in_progress' => __('crm.task_statuses.in_progress'),
                        'completed' => __('crm.task_statuses.completed'),
                        'canceled' => __('crm.task_statuses.canceled'),
                    ])
                    ->default('open')
                    ->required(),
                Select::make('priority')
                    ->label(__('crm.resources.fields.priority'))
                    ->options([
                        'low' => __('crm.priorities.low'),
                        'normal' => __('crm.priorities.normal'),
                        'high' => __('crm.priorities.high'),
                        'urgent' => __('crm.priorities.urgent'),
                    ])
                    ->default('normal')
                    ->required(),
                DateTimePicker::make('due_at')->label(__('crm.resources.fields.due_at')),
                Select::make('owner_id')
                    ->label(__('crm.resources.fields.owner'))
                    ->options(User::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('due_at', 'asc')
            ->columns([
                TextColumn::make('title')->label(__('crm.resources.fields.title'))->searchable(),
                TextColumn::make('subject_label')
                    ->label(__('crm.resources.fields.subject'))
                    ->state(fn (CrmTask $record): string => CrmSubjectRegistry::recordLabel($record->subject)),
                TextColumn::make('status')
                    ->label(__('crm.resources.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? __("crm.task_statuses.{$state}") : __('crm.common.not_available')),
                TextColumn::make('priority')
                    ->label(__('crm.resources.fields.priority'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? __("crm.priorities.{$state}") : __('crm.common.not_available')),
                TextColumn::make('owner.name')->label(__('crm.resources.fields.owner'))->searchable(),
                TextColumn::make('due_at')->label(__('crm.resources.fields.due_at'))->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('crm.resources.filters.status'))
                    ->options([
                        'open' => __('crm.task_statuses.open'),
                        'in_progress' => __('crm.task_statuses.in_progress'),
                        'completed' => __('crm.task_statuses.completed'),
                        'canceled' => __('crm.task_statuses.canceled'),
                    ]),
                Filter::make('overdue')
                    ->label(__('crm.resources.filters.overdue'))
                    ->query(fn (Builder $query): Builder => $query
                        ->whereIn('status', ['open', 'in_progress'])
                        ->whereNotNull('due_at')
                        ->where('due_at', '<', now())),
            ])
            ->recordActions([
                Action::make('complete')
                    ->label(__('crm.resources.actions.complete'))
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->authorize('complete')
                    ->visible(fn (CrmTask $record): bool => in_array($record->status, ['open', 'in_progress'], true))
                    ->action(fn (CrmTask $record) => app(CrmActivityService::class)->completeTask($record)),
                \Filament\Actions\EditAction::make(),
            ])
            ->toolbarActions([
                \Filament\Actions\CreateAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCrmTasks::route('/'),
            'create' => Pages\CreateCrmTask::route('/create'),
            'edit' => Pages\EditCrmTask::route('/{record}/edit'),
        ];
    }
}
