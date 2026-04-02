<?php

namespace App\Modules\CRM\Filament\Resources;

use App\Modules\CRM\Filament\Resources\CrmNoteResource\Pages;
use App\Modules\CRM\Models\CrmNote;
use App\Modules\CRM\Support\CrmSubjectRegistry;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CrmNoteResource extends Resource
{
    protected static ?string $model = CrmNote::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?int $navigationSort = 9;

    public static function getNavigationGroup(): ?string
    {
        return __('core.navigation.crm');
    }

    public static function getNavigationLabel(): string
    {
        return __('crm.resources.note.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('crm.resources.note.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crm.resources.note.plural');
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
                Select::make('visibility')
                    ->label(__('crm.resources.fields.visibility'))
                    ->options([
                        'internal' => __('crm.visibility.internal'),
                        'external' => __('crm.visibility.external'),
                    ])
                    ->default('internal')
                    ->required(),
                Textarea::make('note')
                    ->label(__('crm.resources.fields.note'))
                    ->required()
                    ->rows(6)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('subject_label')
                    ->label(__('crm.resources.fields.subject'))
                    ->state(fn (CrmNote $record): string => CrmSubjectRegistry::recordLabel($record->subject)),
                TextColumn::make('visibility')
                    ->label(__('crm.resources.fields.visibility'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? __("crm.visibility.{$state}") : __('crm.common.not_available')),
                TextColumn::make('note')->label(__('crm.resources.fields.note'))->limit(90)->wrap(),
                TextColumn::make('creator.name')->label(__('crm.resources.fields.created_by'))->searchable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('visibility')
                    ->label(__('crm.resources.fields.visibility'))
                    ->options([
                        'internal' => __('crm.visibility.internal'),
                        'external' => __('crm.visibility.external'),
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
            'index' => Pages\ListCrmNotes::route('/'),
            'create' => Pages\CreateCrmNote::route('/create'),
            'edit' => Pages\EditCrmNote::route('/{record}/edit'),
        ];
    }
}
