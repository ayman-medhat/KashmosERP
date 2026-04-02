<?php

namespace App\Modules\CRM\Filament\Resources;

use App\Modules\CRM\Filament\Resources\CrmPipelineStageResource\Pages;
use App\Modules\CRM\Models\CrmPipelineStage;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CrmPipelineStageResource extends Resource
{
    protected static ?string $model = CrmPipelineStage::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-funnel';

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string
    {
        return __('core.navigation.crm');
    }

    public static function getNavigationLabel(): string
    {
        return __('crm.resources.pipeline_stage.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('crm.resources.pipeline_stage.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crm.resources.pipeline_stage.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')->label(__('crm.resources.fields.code'))->required()->maxLength(50),
                TextInput::make('name_translations.en')->label(__('crm.resources.fields.name_en'))->required()->maxLength(255),
                TextInput::make('name_translations.ar')->label(__('crm.resources.fields.name_ar'))->required()->maxLength(255),
                TextInput::make('stage_order')->label(__('crm.resources.fields.stage_order'))->numeric()->required()->minValue(1),
                TextInput::make('default_probability')->label(__('crm.resources.fields.default_probability'))->numeric()->required()->minValue(0)->maxValue(100),
                ColorPicker::make('color')->label(__('crm.resources.fields.color'))->default('#3B82F6'),
                Toggle::make('is_won_stage')->label(__('crm.resources.fields.is_won_stage')),
                Toggle::make('is_lost_stage')->label(__('crm.resources.fields.is_lost_stage')),
                Toggle::make('is_active')->label(__('crm.resources.fields.is_active'))->default(true),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('stage_order')
            ->columns([
                TextColumn::make('stage_order')->label(__('crm.resources.fields.stage_order'))->sortable(),
                TextColumn::make('code')->label(__('crm.resources.fields.code'))->searchable()->sortable(),
                TextColumn::make('name')->label(__('crm.resources.fields.name'))->searchable(),
                TextColumn::make('default_probability')->label(__('crm.resources.fields.default_probability')),
                TextColumn::make('is_won_stage')
                    ->label(__('crm.resources.fields.is_won_stage'))
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? __('crm.resources.helpers.won') : __('crm.resources.helpers.none')),
                TextColumn::make('is_lost_stage')
                    ->label(__('crm.resources.fields.is_lost_stage'))
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? __('crm.resources.helpers.lost') : __('crm.resources.helpers.none')),
                TextColumn::make('is_active')
                    ->label(__('crm.resources.fields.is_active'))
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? __('crm.common.active') : __('crm.common.inactive')),
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
            'index' => Pages\ListCrmPipelineStages::route('/'),
            'create' => Pages\CreateCrmPipelineStage::route('/create'),
            'edit' => Pages\EditCrmPipelineStage::route('/{record}/edit'),
        ];
    }
}
