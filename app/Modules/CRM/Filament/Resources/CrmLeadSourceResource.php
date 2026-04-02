<?php

namespace App\Modules\CRM\Filament\Resources;

use App\Modules\CRM\Filament\Resources\CrmLeadSourceResource\Pages;
use App\Modules\CRM\Models\CrmLeadSource;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CrmLeadSourceResource extends Resource
{
    protected static ?string $model = CrmLeadSource::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';

    protected static ?int $navigationSort = 6;

    public static function getNavigationGroup(): ?string
    {
        return __('core.navigation.crm');
    }

    public static function getNavigationLabel(): string
    {
        return __('crm.resources.lead_source.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('crm.resources.lead_source.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crm.resources.lead_source.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')->label(__('crm.resources.fields.code'))->required()->maxLength(50),
                TextInput::make('name_translations.en')->label(__('crm.resources.fields.name_en'))->required()->maxLength(255),
                TextInput::make('name_translations.ar')->label(__('crm.resources.fields.name_ar'))->required()->maxLength(255),
                Toggle::make('is_active')->label(__('crm.resources.fields.is_active'))->default(true),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('code')->label(__('crm.resources.fields.code'))->searchable()->sortable(),
                TextColumn::make('name')->label(__('crm.resources.fields.name'))->searchable(),
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
            'index' => Pages\ListCrmLeadSources::route('/'),
            'create' => Pages\CreateCrmLeadSource::route('/create'),
            'edit' => Pages\EditCrmLeadSource::route('/{record}/edit'),
        ];
    }
}
