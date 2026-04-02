<?php

namespace App\Modules\Accounting\Filament\Resources;

use App\Modules\Accounting\Filament\Resources\JournalEntryResource\Pages;
use App\Modules\Accounting\Models\ChartOfAccount;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Accounting\Services\JournalEntryService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class JournalEntryResource extends Resource
{
    protected static ?string $model = JournalEntry::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-currency-dollar';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('core.navigation.accounting');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('entry_no')->label('Entry No')->placeholder('Auto')->maxLength(100),
                DatePicker::make('entry_date')->required()->default(now()),
                TextInput::make('reference_no')->maxLength(100),
                Textarea::make('description_translations.en')->label('Description (English)'),
                Textarea::make('description_translations.ar')->label('Description (Arabic)'),
                Repeater::make('lines')
                    ->schema([
                        Select::make('chart_of_account_id')
                            ->label('Account')
                            ->options(ChartOfAccount::query()
                                ->where('is_active', true)
                                ->orderBy('code')
                                ->get()
                                ->mapWithKeys(fn (ChartOfAccount $account): array => [$account->id => $account->code.' - '.$account->name])
                                ->all())
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('debit')->numeric()->default(0)->required(),
                        TextInput::make('credit')->numeric()->default(0)->required(),
                        TextInput::make('description_translations.en')->label('Line Desc (EN)'),
                        TextInput::make('description_translations.ar')->label('Line Desc (AR)'),
                    ])
                    ->defaultItems(2)
                    ->minItems(2)
                    ->columns(3)
                    ->columnSpanFull()
                    ->required(),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('entry_no')->searchable()->sortable(),
                TextColumn::make('entry_date')->date()->sortable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('total_debit')->money('EGP'),
                TextColumn::make('total_credit')->money('EGP'),
                TextColumn::make('posted_at')->dateTime(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'posted' => 'Posted',
                        'reversed' => 'Reversed',
                    ]),
            ])
            ->recordActions([
                Action::make('post')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->authorize('post')
                    ->visible(fn (JournalEntry $record): bool => $record->status === 'draft')
                    ->action(function (JournalEntry $record): void {
                        app(JournalEntryService::class)->post($record);

                        Notification::make()
                            ->title('Journal entry posted successfully.')
                            ->success()
                            ->send();
                    }),
                \Filament\Actions\ViewAction::make(),
            ])
            ->toolbarActions([
                \Filament\Actions\CreateAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJournalEntries::route('/'),
            'create' => Pages\CreateJournalEntry::route('/create'),
            'view' => Pages\ViewJournalEntry::route('/{record}'),
        ];
    }
}

