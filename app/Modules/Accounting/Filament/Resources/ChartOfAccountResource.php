<?php

namespace App\Modules\Accounting\Filament\Resources;

use App\Modules\Accounting\Filament\Resources\ChartOfAccountResource\Pages;
use App\Modules\Accounting\Models\ChartOfAccount;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ChartOfAccountResource extends Resource
{
    protected static ?string $model = ChartOfAccount::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calculator';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('core.navigation.accounting');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required()
                    ->maxLength(50),
                TextInput::make('name_translations.en')
                    ->label('Name (English)')
                    ->required()
                    ->maxLength(255),
                TextInput::make('name_translations.ar')
                    ->label('Name (Arabic)')
                    ->required()
                    ->maxLength(255),
                Select::make('account_type')
                    ->required()
                    ->options([
                        'asset' => 'Asset',
                        'liability' => 'Liability',
                        'equity' => 'Equity',
                        'revenue' => 'Revenue',
                        'expense' => 'Expense',
                    ]),
                Select::make('normal_balance')
                    ->required()
                    ->default('debit')
                    ->options([
                        'debit' => 'Debit',
                        'credit' => 'Credit',
                    ]),
                Select::make('parent_account_id')
                    ->label('Parent Account')
                    ->options(ChartOfAccount::query()
                        ->orderBy('code')
                        ->get()
                        ->mapWithKeys(fn (ChartOfAccount $account): array => [$account->id => $account->code.' - '.$account->name])
                        ->all())
                    ->searchable()
                    ->preload(),
                Toggle::make('is_active')
                    ->default(true),
                Toggle::make('is_system'),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('code')
            ->columns([
                TextColumn::make('code')->searchable()->sortable(),
                TextColumn::make('name')->label('Name')->searchable(),
                TextColumn::make('account_type')->badge(),
                TextColumn::make('normal_balance')->badge(),
                TextColumn::make('parent.code')->label('Parent'),
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                SelectFilter::make('account_type')
                    ->options([
                        'asset' => 'Asset',
                        'liability' => 'Liability',
                        'equity' => 'Equity',
                        'revenue' => 'Revenue',
                        'expense' => 'Expense',
                    ]),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\ViewAction::make(),
            ])
            ->toolbarActions([
                \Filament\Actions\CreateAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChartOfAccounts::route('/'),
            'create' => Pages\CreateChartOfAccount::route('/create'),
            'edit' => Pages\EditChartOfAccount::route('/{record}/edit'),
            'view' => Pages\ViewChartOfAccount::route('/{record}'),
        ];
    }
}

