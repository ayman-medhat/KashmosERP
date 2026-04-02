<?php

namespace App\Modules\Purchasing\Filament\Resources;

use App\Modules\Purchasing\Filament\Resources\SupplierBillResource\Pages;
use App\Modules\Purchasing\Models\PurchaseReceipt;
use App\Modules\Purchasing\Models\SupplierBill;
use App\Modules\Purchasing\Services\SupplierBillService;
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

class SupplierBillResource extends Resource
{
    protected static ?string $model = SupplierBill::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'Purchasing';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('bill_no')->label('Bill No')->placeholder('Auto')->maxLength(100),
                Select::make('purchase_receipt_id')
                    ->label('Purchase Receipt')
                    ->options(
                        PurchaseReceipt::query()
                            ->with(['order.supplier'])
                            ->where('status', 'confirmed')
                            ->whereDoesntHave('supplierBill')
                            ->orderByDesc('id')
                            ->get()
                            ->mapWithKeys(fn (PurchaseReceipt $receipt): array => [
                                $receipt->id => $receipt->receipt_no.' - '.($receipt->order?->supplier?->name ?? 'Supplier'),
                            ])
                            ->all()
                    )
                    ->searchable()
                    ->preload()
                    ->required(),
                DatePicker::make('bill_date')->required()->default(now()),
                DatePicker::make('due_date')->default(now()),
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
                TextColumn::make('bill_no')->searchable()->sortable(),
                TextColumn::make('bill_date')->date()->sortable(),
                TextColumn::make('supplier.name')->label('Supplier')->searchable(),
                TextColumn::make('receipt.receipt_no')->label('Receipt')->searchable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('grand_total')->money('EGP'),
                TextColumn::make('paid_total')->money('EGP'),
                TextColumn::make('outstanding_amount')->label('Outstanding')->money('EGP'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'posted' => 'Posted',
                        'partially_paid' => 'Partially Paid',
                        'paid' => 'Paid',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->recordActions([
                Action::make('post')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->authorize('post')
                    ->visible(fn (SupplierBill $record): bool => $record->status === 'draft')
                    ->action(fn (SupplierBill $record) => app(SupplierBillService::class)->post($record)),
                \Filament\Actions\ViewAction::make(),
            ])
            ->toolbarActions([
                \Filament\Actions\CreateAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupplierBills::route('/'),
            'create' => Pages\CreateSupplierBill::route('/create'),
            'view' => Pages\ViewSupplierBill::route('/{record}'),
        ];
    }
}

