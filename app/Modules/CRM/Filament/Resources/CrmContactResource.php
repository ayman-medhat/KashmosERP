<?php

namespace App\Modules\CRM\Filament\Resources;

use App\Core\Models\User;
use App\Modules\CRM\Filament\Pages\CrmTimelinePage;
use App\Modules\CRM\Filament\Resources\CrmContactResource\Pages;
use App\Modules\CRM\Models\CrmAccount;
use App\Modules\CRM\Models\CrmContact;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CrmContactResource extends Resource
{
    protected static ?string $model = CrmContact::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('core.navigation.crm');
    }

    public static function getNavigationLabel(): string
    {
        return __('crm.resources.contact.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('crm.resources.contact.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crm.resources.contact.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('crm_account_id')
                    ->label(__('crm.resources.fields.account'))
                    ->options(CrmAccount::query()->orderBy('id')->get()->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                TextInput::make('first_name')->label(__('crm.resources.fields.first_name'))->required()->maxLength(100),
                TextInput::make('last_name')->label(__('crm.resources.fields.last_name'))->maxLength(100),
                TextInput::make('job_title')->label(__('crm.resources.fields.job_title'))->maxLength(150),
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
                TextColumn::make('name')->label(__('crm.resources.fields.name'))->searchable(),
                TextColumn::make('account.name')->label(__('crm.resources.fields.account'))->searchable(),
                TextColumn::make('email')->label(__('crm.resources.fields.email'))->searchable(),
                TextColumn::make('phone')->label(__('crm.resources.fields.phone'))->searchable(),
                TextColumn::make('owner.name')->label(__('crm.resources.fields.owner'))->searchable(),
                TextColumn::make('is_active')
                    ->label(__('crm.resources.fields.is_active'))
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? __('crm.common.active') : __('crm.common.inactive')),
            ])
            ->recordActions([
                Action::make('timeline')
                    ->label(__('crm.resources.actions.timeline'))
                    ->icon('heroicon-o-clock')
                    ->url(fn (CrmContact $record): string => CrmTimelinePage::getUrl([
                        'subject_type' => CrmContact::class,
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
            'index' => Pages\ListCrmContacts::route('/'),
            'create' => Pages\CreateCrmContact::route('/create'),
            'edit' => Pages\EditCrmContact::route('/{record}/edit'),
        ];
    }
}
