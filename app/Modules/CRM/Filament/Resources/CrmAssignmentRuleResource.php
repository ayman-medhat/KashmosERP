<?php

namespace App\Modules\CRM\Filament\Resources;

use App\Core\Models\User;
use App\Modules\CRM\Filament\Resources\CrmAssignmentRuleResource\Pages;
use App\Modules\CRM\Models\CrmAssignmentRule;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CrmAssignmentRuleResource extends Resource
{
    protected static ?string $model = CrmAssignmentRule::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?int $navigationSort = 12;

    public static function getNavigationGroup(): ?string
    {
        return __('core.navigation.crm');
    }

    public static function getNavigationLabel(): string
    {
        return __('crm.resources.assignment_rule.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('crm.resources.assignment_rule.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crm.resources.assignment_rule.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('crm.resources.fields.name'))
                    ->required()
                    ->maxLength(255),
                Select::make('entity_type')
                    ->label(__('crm.resources.fields.entity_type'))
                    ->options([
                        'lead' => __('crm.assignment.entity_types.lead'),
                        'opportunity' => __('crm.assignment.entity_types.opportunity'),
                    ])
                    ->required(),
                Select::make('assignment_strategy')
                    ->label(__('crm.resources.fields.assignment_strategy'))
                    ->options([
                        'round_robin' => __('crm.assignment.strategies.round_robin'),
                        'least_loaded' => __('crm.assignment.strategies.least_loaded'),
                        'manual' => __('crm.assignment.strategies.manual'),
                    ])
                    ->required(),
                TextInput::make('priority')
                    ->label(__('crm.resources.fields.priority'))
                    ->numeric()
                    ->minValue(1)
                    ->default(100)
                    ->required(),
                Select::make('assigned_user_ids')
                    ->label(__('crm.resources.fields.assignable_users'))
                    ->multiple()
                    ->options(User::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),
                Toggle::make('is_active')
                    ->label(__('crm.resources.fields.is_active'))
                    ->default(true)
                    ->required(),
                KeyValue::make('conditions')
                    ->label(__('crm.resources.fields.conditions'))
                    ->keyLabel(__('crm.resources.fields.condition_field'))
                    ->valueLabel(__('crm.resources.fields.condition_expected_value'))
                    ->reorderable()
                    ->addActionLabel(__('crm.resources.actions.add_condition'))
                    ->columnSpanFull()
                    ->helperText(__('crm.resources.helpers.condition_example')),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('priority')
            ->columns([
                TextColumn::make('name')->label(__('crm.resources.fields.name'))->searchable(),
                TextColumn::make('entity_type')
                    ->label(__('crm.resources.fields.entity_type'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? __("crm.assignment.entity_types.{$state}") : __('crm.common.not_available')),
                TextColumn::make('assignment_strategy')
                    ->label(__('crm.resources.fields.assignment_strategy'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? __("crm.assignment.strategies.{$state}") : __('crm.common.not_available')),
                TextColumn::make('priority')->label(__('crm.resources.fields.priority'))->sortable(),
                TextColumn::make('assigned_users_count')
                    ->label(__('crm.resources.fields.users'))
                    ->state(fn (CrmAssignmentRule $record): int => count($record->assigned_user_ids ?? [])),
                IconColumn::make('is_active')->label(__('crm.resources.fields.is_active'))->boolean(),
                TextColumn::make('updated_at')->dateTime()->label(__('crm.resources.fields.updated')),
            ])
            ->filters([
                SelectFilter::make('entity_type')
                    ->label(__('crm.resources.filters.entity_type'))
                    ->options([
                        'lead' => __('crm.assignment.entity_types.lead'),
                        'opportunity' => __('crm.assignment.entity_types.opportunity'),
                    ]),
                SelectFilter::make('assignment_strategy')
                    ->label(__('crm.resources.filters.assignment_strategy'))
                    ->options([
                        'round_robin' => __('crm.assignment.strategies.round_robin'),
                        'least_loaded' => __('crm.assignment.strategies.least_loaded'),
                        'manual' => __('crm.assignment.strategies.manual'),
                    ]),
                SelectFilter::make('is_active')
                    ->label(__('crm.resources.filters.is_active'))
                    ->options([
                        '1' => __('crm.common.active'),
                        '0' => __('crm.common.inactive'),
                    ]),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make(),
            ])
            ->toolbarActions([
                \Filament\Actions\CreateAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCrmAssignmentRules::route('/'),
            'create' => Pages\CreateCrmAssignmentRule::route('/create'),
            'edit' => Pages\EditCrmAssignmentRule::route('/{record}/edit'),
        ];
    }
}
