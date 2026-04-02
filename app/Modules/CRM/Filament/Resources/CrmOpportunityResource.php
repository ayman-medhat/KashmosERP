<?php

namespace App\Modules\CRM\Filament\Resources;

use App\Core\Models\User;
use App\Modules\CRM\Filament\Pages\CrmPipelineBoardPage;
use App\Modules\CRM\Filament\Pages\CrmTimelinePage;
use App\Modules\CRM\Filament\Resources\CrmOpportunityResource\Pages;
use App\Modules\CRM\Models\CrmAccount;
use App\Modules\CRM\Models\CrmContact;
use App\Modules\CRM\Models\CrmLead;
use App\Modules\CRM\Models\CrmOpportunity;
use App\Modules\CRM\Models\CrmPipelineStage;
use App\Modules\CRM\Services\CrmOpportunityService;
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

class CrmOpportunityResource extends Resource
{
    protected static ?string $model = CrmOpportunity::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return __('core.navigation.crm');
    }

    public static function getNavigationLabel(): string
    {
        return __('crm.resources.opportunity.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('crm.resources.opportunity.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crm.resources.opportunity.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('opportunity_no')->label(__('crm.resources.fields.opportunity_no'))->placeholder(__('crm.resources.helpers.auto'))->maxLength(50),
                TextInput::make('name')->label(__('crm.resources.fields.name'))->required()->maxLength(255),
                Select::make('crm_pipeline_stage_id')
                    ->label(__('crm.resources.fields.stage'))
                    ->options(CrmPipelineStage::query()->where('is_active', true)->orderBy('stage_order')->get()->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),
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
                Select::make('crm_lead_id')
                    ->label(__('crm.resources.fields.lead'))
                    ->options(CrmLead::query()->orderBy('id')->get()->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                TextInput::make('probability')->label(__('crm.resources.fields.probability_percent'))->numeric()->required()->minValue(0)->maxValue(100),
                TextInput::make('expected_value')->label(__('crm.resources.fields.expected_value'))->numeric()->required(),
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
                TextColumn::make('opportunity_no')->label(__('crm.resources.fields.opportunity_no'))->searchable()->sortable(),
                TextColumn::make('name')->label(__('crm.resources.fields.name'))->searchable(),
                TextColumn::make('stage.name')->label(__('crm.resources.fields.stage'))->searchable(),
                TextColumn::make('owner.name')->label(__('crm.resources.fields.owner'))->searchable(),
                TextColumn::make('status')
                    ->label(__('crm.resources.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? __("crm.opportunity_statuses.{$state}") : __('crm.common.not_available')),
                TextColumn::make('probability')->label(__('crm.resources.fields.probability_percent')),
                TextColumn::make('expected_value')->label(__('crm.resources.fields.expected_value'))->money('EGP'),
                TextColumn::make('expected_close_date')->label(__('crm.resources.fields.expected_close_date'))->date(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('crm.resources.filters.status'))
                    ->options([
                        'open' => __('crm.opportunity_statuses.open'),
                        'won' => __('crm.opportunity_statuses.won'),
                        'lost' => __('crm.opportunity_statuses.lost'),
                    ]),
            ])
            ->recordActions([
                Action::make('move_stage')
                    ->label(__('crm.resources.actions.move_stage'))
                    ->color('primary')
                    ->requiresConfirmation()
                    ->authorize('moveStage')
                    ->form([
                        Select::make('crm_pipeline_stage_id')
                            ->label(__('crm.resources.fields.new_stage'))
                            ->options(CrmPipelineStage::query()->where('is_active', true)->orderBy('stage_order')->get()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('note')->label(__('crm.resources.fields.note'))->maxLength(255),
                    ])
                    ->action(function (CrmOpportunity $record, array $data): void {
                        $stage = CrmPipelineStage::query()->findOrFail($data['crm_pipeline_stage_id']);
                        app(CrmOpportunityService::class)->moveStage($record, $stage, $data['note'] ?? null);
                    }),
                Action::make('timeline')
                    ->label(__('crm.resources.actions.timeline'))
                    ->icon('heroicon-o-clock')
                    ->url(fn (CrmOpportunity $record): string => CrmTimelinePage::getUrl([
                        'subject_type' => CrmOpportunity::class,
                        'subject_id' => $record->id,
                    ])),
                \Filament\Actions\EditAction::make(),
            ])
            ->toolbarActions([
                Action::make('pipeline_board')
                    ->label(__('crm.resources.actions.pipeline_board'))
                    ->icon('heroicon-o-view-columns')
                    ->url(CrmPipelineBoardPage::getUrl()),
                \Filament\Actions\CreateAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCrmOpportunities::route('/'),
            'create' => Pages\CreateCrmOpportunity::route('/create'),
            'edit' => Pages\EditCrmOpportunity::route('/{record}/edit'),
        ];
    }
}
