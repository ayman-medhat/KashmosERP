<?php

namespace App\Modules\CRM\Filament\Resources;

use App\Modules\CRM\Filament\Resources\CrmAccountResource\Pages;
use App\Modules\CRM\Filament\Pages\CrmTimelinePage;
use App\Modules\CRM\Models\CrmAccount;
use App\Core\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CrmAccountResource extends Resource
{
    protected static ?string $model = CrmAccount::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('core.navigation.crm');
    }

    public static function getNavigationLabel(): string
    {
        return __('crm.resources.account.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('crm.resources.account.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crm.resources.account.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')->label(__('crm.resources.fields.code'))->required()->maxLength(50),
                TextInput::make('name_translations.en')->label(__('crm.resources.fields.name_en'))->required()->maxLength(255),
                TextInput::make('name_translations.ar')->label(__('crm.resources.fields.name_ar'))->required()->maxLength(255),
                TextInput::make('industry')->label(__('crm.resources.fields.industry'))->maxLength(100),
                TextInput::make('website')->label(__('crm.resources.fields.website'))->url()->maxLength(255),
                TextInput::make('email')->label(__('crm.resources.fields.email'))->email()->maxLength(255),
                TextInput::make('phone')->label(__('crm.resources.fields.phone'))->tel()->maxLength(50),
                TextInput::make('address_translations.en')->label(__('crm.resources.fields.address_en'))->maxLength(255),
                TextInput::make('address_translations.ar')->label(__('crm.resources.fields.address_ar'))->maxLength(255),
                Select::make('owner_id')
                    ->label(__('crm.resources.fields.owner'))
                    ->options(User::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
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
                TextColumn::make('industry')->label(__('crm.resources.fields.industry'))->searchable(),
                TextColumn::make('owner.name')->label(__('crm.resources.fields.owner'))->searchable(),
                TextColumn::make('last_activity_at')->label(__('crm.resources.fields.last_activity_at'))->dateTime(),
                TextColumn::make('next_follow_up_at')->label(__('crm.resources.fields.next_follow_up_at'))->dateTime(),
                TextColumn::make('is_active')
                    ->label(__('crm.resources.fields.is_active'))
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? __('crm.common.active') : __('crm.common.inactive')),
            ])
            ->recordActions([
                Action::make('timeline')
                    ->label(__('crm.resources.actions.timeline'))
                    ->icon('heroicon-o-clock')
                    ->url(fn (CrmAccount $record): string => CrmTimelinePage::getUrl([
                        'subject_type' => CrmAccount::class,
                        'subject_id' => $record->id,
                    ])),
                \Filament\Actions\EditAction::make(),
            ])
            ->toolbarActions([
                \Filament\Actions\CreateAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCrmAccounts::route('/'),
            'create' => Pages\CreateCrmAccount::route('/create'),
            'edit' => Pages\EditCrmAccount::route('/{record}/edit'),
        ];
    }
}
