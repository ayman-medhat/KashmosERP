<?php

namespace App\Modules\Sales\Filament\Resources;

use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\Product;
use App\Modules\MasterData\Models\Warehouse;
use App\Modules\Sales\Filament\Resources\SalesQuotationResource\Pages;
use App\Modules\Sales\Models\SalesQuotation;
use App\Modules\Sales\Services\SalesQuotationService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SalesQuotationResource extends Resource
{
    protected static ?string $model = SalesQuotation::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-duplicate';

    protected static string|\UnitEnum|null $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 0;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('quotation_no')->label('Quotation No')->placeholder('Auto')->maxLength(100),
                DatePicker::make('quotation_date')->required()->default(now()),
                Select::make('customer_id')
                    ->label('Customer')
                    ->options(Customer::query()->orderBy('id')->get()->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('warehouse_id')
                    ->label('Warehouse')
                    ->options(Warehouse::query()->orderBy('id')->get()->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload()
                    ->required(),
                Textarea::make('notes_translations.en')->label('Notes (English)'),
                Textarea::make('notes_translations.ar')->label('Notes (Arabic)'),
                Repeater::make('items')
                    ->schema([
                        Select::make('product_id')
                            ->label('Product')
                            ->options(Product::query()->orderBy('id')->get()->pluck('name', 'id')->all())
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('quantity')->numeric()->required(),
                        TextInput::make('unit_price')->numeric()->required(),
                        TextInput::make('tax_rate')->numeric()->default(0),
                    ])
                    ->defaultItems(1)
                    ->minItems(1)
                    ->columns(4)
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
                TextColumn::make('quotation_no')->searchable()->sortable(),
                TextColumn::make('quotation_date')->date()->sortable(),
                TextColumn::make('customer.name')->label('Customer')->searchable(),
                TextColumn::make('warehouse.name')->label('Warehouse'),
                TextColumn::make('status')->badge(),
                TextColumn::make('grand_total')->money('EGP'),
                TextColumn::make('convertedSalesOrder.order_no')->label('Sales Order'),
            ])
            ->recordActions([
                Action::make('submit')
                    ->color('warning')
                    ->icon('heroicon-o-paper-airplane')
                    ->requiresConfirmation()
                    ->authorize('submit')
                    ->visible(fn (SalesQuotation $record): bool => $record->status === 'draft')
                    ->action(fn (SalesQuotation $record) => app(SalesQuotationService::class)->submit($record)),
                Action::make('approve')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->authorize('approve')
                    ->visible(fn (SalesQuotation $record): bool => $record->status === 'submitted')
                    ->action(fn (SalesQuotation $record) => app(SalesQuotationService::class)->approve($record)),
                Action::make('convert')
                    ->color('primary')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->authorize('convert')
                    ->visible(fn (SalesQuotation $record): bool => $record->status === 'approved')
                    ->action(fn (SalesQuotation $record) => app(SalesQuotationService::class)->convertToSalesOrder($record)),
                Action::make('cancel')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->authorize('cancel')
                    ->visible(fn (SalesQuotation $record): bool => in_array($record->status, ['draft', 'submitted'], true))
                    ->action(fn (SalesQuotation $record) => app(SalesQuotationService::class)->cancel($record)),
                \Filament\Actions\ViewAction::make(),
            ])
            ->toolbarActions([
                \Filament\Actions\CreateAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalesQuotations::route('/'),
            'create' => Pages\CreateSalesQuotation::route('/create'),
            'view' => Pages\ViewSalesQuotation::route('/{record}'),
        ];
    }
}
