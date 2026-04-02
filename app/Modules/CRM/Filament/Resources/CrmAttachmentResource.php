<?php

namespace App\Modules\CRM\Filament\Resources;

use App\Modules\CRM\Filament\Resources\CrmAttachmentResource\Pages;
use App\Modules\CRM\Models\CrmAttachment;
use App\Modules\CRM\Support\CrmSubjectRegistry;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CrmAttachmentResource extends Resource
{
    protected static ?string $model = CrmAttachment::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-paper-clip';

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): ?string
    {
        return __('core.navigation.crm');
    }

    public static function getNavigationLabel(): string
    {
        return __('crm.resources.attachment.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('crm.resources.attachment.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crm.resources.attachment.plural');
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
                FileUpload::make('file_path')
                    ->label(__('crm.resources.fields.file'))
                    ->disk('public')
                    ->directory('crm/attachments')
                    ->visibility('public')
                    ->preserveFilenames()
                    ->downloadable()
                    ->openable()
                    ->required()
                    ->afterStateUpdated(function (Set $set, mixed $state): void {
                        if (is_string($state) && $state !== '') {
                            $set('file_name', basename($state));
                        }
                    }),
                TextInput::make('file_name')
                    ->label(__('crm.resources.fields.file_name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('disk')
                    ->label(__('crm.resources.fields.disk'))
                    ->default('public')
                    ->required()
                    ->maxLength(50),
                TextInput::make('mime_type')
                    ->label(__('crm.resources.fields.mime_type'))
                    ->maxLength(120),
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
                    ->state(fn (CrmAttachment $record): string => CrmSubjectRegistry::recordLabel($record->subject)),
                TextColumn::make('file_name')->label(__('crm.resources.fields.file_name'))->searchable(),
                TextColumn::make('mime_type')->label(__('crm.resources.fields.type'))->toggleable(),
                TextColumn::make('size_bytes')
                    ->label(__('crm.resources.fields.size_bytes'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('creator.name')->label(__('crm.resources.fields.created_by'))->searchable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
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
            'index' => Pages\ListCrmAttachments::route('/'),
            'create' => Pages\CreateCrmAttachment::route('/create'),
            'edit' => Pages\EditCrmAttachment::route('/{record}/edit'),
        ];
    }
}
