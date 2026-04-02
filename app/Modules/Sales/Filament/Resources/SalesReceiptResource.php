<?php

namespace App\Modules\Sales\Filament\Resources;

use App\Modules\Sales\Filament\Resources\SalesReceiptResource\Pages;
use App\Modules\Sales\Models\SalesInvoice;
use App\Modules\Sales\Models\SalesReceipt;
use App\Modules\Sales\Services\SalesReceiptService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SalesReceiptResource extends Resource
{
    protected static ?string $model = SalesReceipt::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|\UnitEnum|null $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('receipt_no')->label('Receipt No')->placeholder('Auto')->maxLength(100),
                Select::make('sales_invoice_id')
                    ->label('Sales Invoice')
                    ->options(
                        SalesInvoice::query()
                            ->with('customer')
                            ->whereIn('status', ['posted', 'partially_paid'])
                            ->whereRaw('paid_total < grand_total')
                            ->orderByDesc('id')
                            ->get()
                            ->mapWithKeys(fn (SalesInvoice $invoice): array => [
                                $invoice->id => sprintf(
                                    '%s - %s (outstanding %.4f)',
                                    $invoice->invoice_no,
                                    $invoice->customer?->name ?? 'Customer',
                                    $invoice->outstanding_amount
                                ),
                            ])
                            ->all()
                    )
                    ->searchable()
                    ->preload()
                    ->required(),
                DatePicker::make('receipt_date')->required()->default(now()),
                TextInput::make('amount')->numeric()->required(),
                TextInput::make('payment_method')->maxLength(50),
                TextInput::make('reference_no')->maxLength(100),
                Textarea::make('notes_translations.en')->label('Notes (English)'),
                Textarea::make('notes_translations.ar')->label('Notes (Arabic)'),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('receipt_no')->searchable()->sortable(),
                TextColumn::make('receipt_date')->date()->sortable(),
                TextColumn::make('invoice.invoice_no')->label('Invoice')->searchable(),
                TextColumn::make('customer.name')->label('Customer')->searchable(),
                TextColumn::make('amount')->money('EGP'),
                TextColumn::make('status')->badge(),
                TextColumn::make('payment_method'),
            ])
            ->recordActions([
                \Filament\Actions\ViewAction::make(),
            ])
            ->toolbarActions([
                \Filament\Actions\CreateAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalesReceipts::route('/'),
            'create' => Pages\CreateSalesReceipt::route('/create'),
            'view' => Pages\ViewSalesReceipt::route('/{record}'),
        ];
    }
}

