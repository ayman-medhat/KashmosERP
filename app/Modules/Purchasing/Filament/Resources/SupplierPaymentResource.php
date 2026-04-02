<?php

namespace App\Modules\Purchasing\Filament\Resources;

use App\Modules\Purchasing\Filament\Resources\SupplierPaymentResource\Pages;
use App\Modules\Purchasing\Models\SupplierBill;
use App\Modules\Purchasing\Models\SupplierPayment;
use App\Modules\Purchasing\Services\SupplierPaymentService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SupplierPaymentResource extends Resource
{
    protected static ?string $model = SupplierPayment::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|\UnitEnum|null $navigationGroup = 'Purchasing';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('payment_no')->label('Payment No')->placeholder('Auto')->maxLength(100),
                Select::make('supplier_bill_id')
                    ->label('Supplier Bill')
                    ->options(
                        SupplierBill::query()
                            ->with('supplier')
                            ->whereIn('status', ['posted', 'partially_paid'])
                            ->whereRaw('paid_total < grand_total')
                            ->orderByDesc('id')
                            ->get()
                            ->mapWithKeys(fn (SupplierBill $bill): array => [
                                $bill->id => sprintf(
                                    '%s - %s (outstanding %.4f)',
                                    $bill->bill_no,
                                    $bill->supplier?->name ?? 'Supplier',
                                    $bill->outstanding_amount
                                ),
                            ])
                            ->all()
                    )
                    ->searchable()
                    ->preload()
                    ->required(),
                DatePicker::make('payment_date')->required()->default(now()),
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
                TextColumn::make('payment_no')->searchable()->sortable(),
                TextColumn::make('payment_date')->date()->sortable(),
                TextColumn::make('bill.bill_no')->label('Bill')->searchable(),
                TextColumn::make('supplier.name')->label('Supplier')->searchable(),
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
            'index' => Pages\ListSupplierPayments::route('/'),
            'create' => Pages\CreateSupplierPayment::route('/create'),
            'view' => Pages\ViewSupplierPayment::route('/{record}'),
        ];
    }
}

