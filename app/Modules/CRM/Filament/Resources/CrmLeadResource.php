<?php

namespace App\Modules\CRM\Filament\Resources;

use App\Core\Models\User;
use App\Modules\CRM\Filament\Pages\CrmTimelinePage;
use App\Modules\CRM\Filament\Resources\CrmLeadResource\Pages;
use App\Modules\CRM\Models\CrmAccount;
use App\Modules\CRM\Models\CrmContact;
use App\Modules\CRM\Models\CrmLead;
use App\Modules\CRM\Models\CrmLeadSource;
use App\Modules\CRM\Models\CrmPipelineStage;
use App\Modules\CRM\Services\CrmLeadService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CrmLeadResource extends Resource
{
    protected static ?string $model = CrmLead::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-light-bulb';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('core.navigation.crm');
    }

    public static function getNavigationLabel(): string
    {
        return __('crm.resources.lead.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('crm.resources.lead.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crm.resources.lead.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('lead_no')->label(__('crm.resources.fields.lead_no'))->placeholder(__('crm.resources.helpers.auto'))->maxLength(50),
                TextInput::make('name')->label(__('crm.resources.fields.name'))->required()->maxLength(255),
                TextInput::make('email')->label(__('crm.resources.fields.email'))->email()->maxLength(255),
                TextInput::make('phone')->label(__('crm.resources.fields.phone'))->tel()->maxLength(50),
                Select::make('crm_account_id')
                    ->label(__('crm.resources.fields.account'))
                    ->options(CrmAccount::query()->orderBy('id')->get()->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                Select::make('crm_contact_id')
                    ->label(__('crm.resources.fields.contact'))
                    ->options(CrmContact::query()->orderBy('id')->get()->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                Select::make('crm_lead_source_id')
                    ->label(__('crm.resources.fields.lead_source'))
                    ->options(CrmLeadSource::query()->orderBy('id')->get()->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                TextInput::make('expected_value')->label(__('crm.resources.fields.expected_value'))->numeric(),
                DatePicker::make('expected_close_date')->label(__('crm.resources.fields.expected_close_date')),
                Select::make('owner_id')
                    ->label(__('crm.resources.fields.owner'))
                    ->options(User::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                Textarea::make('details.notes')->label(__('crm.resources.fields.notes')),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('lead_no')->label(__('crm.resources.fields.lead_no'))->searchable()->sortable(),
                TextColumn::make('name')->label(__('crm.resources.fields.name'))->searchable(),
                TextColumn::make('source.name')->label(__('crm.resources.fields.source'))->searchable(),
                TextColumn::make('owner.name')->label(__('crm.resources.fields.owner'))->searchable(),
                TextColumn::make('status')
                    ->label(__('crm.resources.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? __("crm.lead_statuses.{$state}") : __('crm.common.not_available')),
                TextColumn::make('expected_value')->label(__('crm.resources.fields.expected_value'))->money('EGP'),
                TextColumn::make('next_follow_up_at')->label(__('crm.resources.fields.next_follow_up_at'))->dateTime(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('crm.resources.filters.status'))
                    ->options([
                        'new' => __('crm.lead_statuses.new'),
                        'qualified' => __('crm.lead_statuses.qualified'),
                        'disqualified' => __('crm.lead_statuses.disqualified'),
                        'converted' => __('crm.lead_statuses.converted'),
                    ]),
            ])
            ->recordActions([
                Action::make('qualify')
                    ->label(__('crm.resources.actions.qualify'))
                    ->color('success')
                    ->requiresConfirmation()
                    ->authorize('update')
                    ->visible(fn (CrmLead $record): bool => in_array($record->status, ['new', 'qualified'], true))
                    ->action(fn (CrmLead $record) => app(CrmLeadService::class)->qualify($record)),
                Action::make('disqualify')
                    ->label(__('crm.resources.actions.disqualify'))
                    ->color('danger')
                    ->requiresConfirmation()
                    ->authorize('update')
                    ->visible(fn (CrmLead $record): bool => in_array($record->status, ['new', 'qualified'], true))
                    ->action(fn (CrmLead $record) => app(CrmLeadService::class)->disqualify($record)),
                Action::make('convert')
                    ->label(__('crm.resources.actions.convert'))
                    ->color('primary')
                    ->requiresConfirmation()
                    ->authorize('convert')
                    ->visible(fn (CrmLead $record): bool => in_array($record->status, ['new', 'qualified'], true))
                    ->form([
                        Select::make('crm_pipeline_stage_id')
                            ->label(__('crm.resources.fields.stage'))
                            ->options(CrmPipelineStage::query()->where('is_active', true)->orderBy('stage_order')->get()->pluck('name', 'id'))
                            ->searchable()
                            ->preload(),
                    ])
                    ->action(fn (CrmLead $record, array $data) => app(CrmLeadService::class)->convertToOpportunity($record, $data)),
                Action::make('timeline')
                    ->label(__('crm.resources.actions.timeline'))
                    ->icon('heroicon-o-clock')
                    ->url(fn (CrmLead $record): string => CrmTimelinePage::getUrl([
                        'subject_type' => CrmLead::class,
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
            'index' => Pages\ListCrmLeads::route('/'),
            'create' => Pages\CreateCrmLead::route('/create'),
            'edit' => Pages\EditCrmLead::route('/{record}/edit'),
        ];
    }
}
