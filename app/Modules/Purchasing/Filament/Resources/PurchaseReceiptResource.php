<?php

namespace App\Modules\Purchasing\Filament\Resources;

use App\Modules\Purchasing\Filament\Resources\PurchaseReceiptResource\Pages;
use App\Modules\Purchasing\Models\PurchaseOrder;
use App\Modules\Purchasing\Models\PurchaseOrderItem;
use App\Modules\Purchasing\Models\PurchaseReceipt;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PurchaseReceiptResource extends Resource
{
    protected static ?string $model = PurchaseReceipt::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static string|\UnitEnum|null $navigationGroup = 'Purchasing';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('receipt_no')->label('Receipt No')->placeholder('Auto')->maxLength(100),
                Select::make('purchase_order_id')
                    ->label('Purchase Order')
                    ->options(
                        PurchaseOrder::query()
                            ->whereIn('status', ['approved', 'partially_received'])
                            ->orderByDesc('id')
                            ->get()
                            ->mapWithKeys(fn (PurchaseOrder $order): array => [
                                $order->id => $order->order_no.' ('.$order->status.')',
                            ])
                            ->all()
                    )
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('items', []))
                    ->required(),
                DatePicker::make('received_date')->required()->default(now()),
                Textarea::make('notes_translations.en')->label('Notes (English)'),
                Textarea::make('notes_translations.ar')->label('Notes (Arabic)'),
                Repeater::make('items')
                    ->schema([
                        Select::make('purchase_order_item_id')
                            ->label('Order Item')
                            ->options(fn (Get $get): array => self::availablePurchaseOrderItems(
                                purchaseOrderId: $get->integer('../../purchase_order_id', isNullable: true),
                            ))
                            ->searchable()
                            ->preload()
                            ->distinct()
                            ->disabled(fn (Get $get): bool => blank($get('../../purchase_order_id')))
                            ->required(),
                        TextInput::make('received_qty')
                            ->numeric()
                            ->minValue(0.000001)
                            ->rule(fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get): void {
                                $purchaseOrderId = $get->integer('../../purchase_order_id', isNullable: true);
                                $purchaseOrderItemId = $get->integer('purchase_order_item_id', isNullable: true);

                                if (! $purchaseOrderId || ! $purchaseOrderItemId) {
                                    return;
                                }

                                $remainingQty = self::remainingQuantityForOrderItem(
                                    purchaseOrderItemId: $purchaseOrderItemId,
                                    purchaseOrderId: $purchaseOrderId,
                                );

                                if ($remainingQty === null) {
                                    $fail('The selected order item is invalid for this purchase order.');

                                    return;
                                }

                                if ((float) $value > $remainingQty) {
                                    $fail(sprintf('Received quantity cannot exceed remaining quantity (%.6f).', $remainingQty));
                                }
                            })
                            ->helperText(fn (Get $get): ?string => self::remainingQuantityHint(
                                purchaseOrderItemId: $get->integer('purchase_order_item_id', isNullable: true),
                                purchaseOrderId: $get->integer('../../purchase_order_id', isNullable: true),
                            ))
                            ->required(),
                    ])
                    ->defaultItems(1)
                    ->minItems(1)
                    ->columns(2)
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
                TextColumn::make('receipt_no')->searchable()->sortable(),
                TextColumn::make('received_date')->date()->sortable(),
                TextColumn::make('order.order_no')->label('Order No')->searchable(),
                TextColumn::make('order.supplier.name')->label('Supplier')->searchable(),
                TextColumn::make('warehouse.name')->label('Warehouse')->searchable(),
                TextColumn::make('status')->badge(),
            ])
            ->filters([
                Filter::make('today')
                    ->label('Today')
                    ->query(fn (Builder $query): Builder => $query->whereDate('received_date', today())),
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
            'index' => Pages\ListPurchaseReceipts::route('/'),
            'create' => Pages\CreatePurchaseReceipt::route('/create'),
            'view' => Pages\ViewPurchaseReceipt::route('/{record}'),
        ];
    }

    /**
     * @return array<int, string>
     */
    protected static function availablePurchaseOrderItems(?int $purchaseOrderId): array
    {
        if (! $purchaseOrderId) {
            return [];
        }

        return PurchaseOrderItem::query()
            ->with(['order', 'product'])
            ->where('purchase_order_id', $purchaseOrderId)
            ->whereHas('order', fn ($query) => $query->whereIn('status', ['approved', 'partially_received']))
            ->get()
            ->filter(function (PurchaseOrderItem $item): bool {
                return (float) $item->received_qty < (float) $item->quantity;
            })
            ->mapWithKeys(function (PurchaseOrderItem $item): array {
                $remaining = (float) $item->quantity - (float) $item->received_qty;
                $productName = $item->product?->name ?? 'Product #'.$item->product_id;
                $orderNo = $item->order?->order_no ?? 'PO';

                return [
                    $item->id => sprintf('%s - %s (remaining %.6f)', $orderNo, $productName, $remaining),
                ];
            })
            ->all();
    }

    protected static function remainingQuantityForOrderItem(int $purchaseOrderItemId, int $purchaseOrderId): ?float
    {
        $item = PurchaseOrderItem::query()
            ->whereKey($purchaseOrderItemId)
            ->where('purchase_order_id', $purchaseOrderId)
            ->first();

        if (! $item) {
            return null;
        }

        return max(0.0, (float) $item->quantity - (float) $item->received_qty);
    }

    protected static function remainingQuantityHint(?int $purchaseOrderItemId, ?int $purchaseOrderId): ?string
    {
        if (! $purchaseOrderItemId || ! $purchaseOrderId) {
            return null;
        }

        $remaining = self::remainingQuantityForOrderItem($purchaseOrderItemId, $purchaseOrderId);

        if ($remaining === null) {
            return null;
        }

        return sprintf('Remaining quantity: %.6f', $remaining);
    }
}
