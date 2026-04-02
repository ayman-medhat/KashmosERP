<?php

namespace App\Modules\Sales\Filament\Resources;

use App\Modules\Sales\Filament\Resources\SalesDeliveryResource\Pages;
use App\Modules\Sales\Models\SalesDelivery;
use App\Modules\Sales\Models\SalesOrder;
use App\Modules\Sales\Models\SalesOrderItem;
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

class SalesDeliveryResource extends Resource
{
    protected static ?string $model = SalesDelivery::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static string|\UnitEnum|null $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('delivery_no')->label('Delivery No')->placeholder('Auto')->maxLength(100),
                Select::make('sales_order_id')
                    ->label('Sales Order')
                    ->options(
                        SalesOrder::query()
                            ->whereIn('status', ['approved', 'partially_delivered'])
                            ->orderByDesc('id')
                            ->get()
                            ->mapWithKeys(fn (SalesOrder $order): array => [
                                $order->id => $order->order_no.' ('.$order->status.')',
                            ])
                            ->all()
                    )
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('items', []))
                    ->required(),
                DatePicker::make('delivery_date')->required()->default(now()),
                Textarea::make('notes_translations.en')->label('Notes (English)'),
                Textarea::make('notes_translations.ar')->label('Notes (Arabic)'),
                Repeater::make('items')
                    ->schema([
                        Select::make('sales_order_item_id')
                            ->label('Order Item')
                            ->options(fn (Get $get): array => self::availableSalesOrderItems(
                                salesOrderId: $get->integer('../../sales_order_id', isNullable: true),
                            ))
                            ->searchable()
                            ->preload()
                            ->distinct()
                            ->disabled(fn (Get $get): bool => blank($get('../../sales_order_id')))
                            ->required(),
                        TextInput::make('delivered_qty')
                            ->numeric()
                            ->minValue(0.000001)
                            ->rule(fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get): void {
                                $salesOrderId = $get->integer('../../sales_order_id', isNullable: true);
                                $salesOrderItemId = $get->integer('sales_order_item_id', isNullable: true);

                                if (! $salesOrderId || ! $salesOrderItemId) {
                                    return;
                                }

                                $remainingQty = self::remainingQuantityForOrderItem(
                                    salesOrderItemId: $salesOrderItemId,
                                    salesOrderId: $salesOrderId,
                                );

                                if ($remainingQty === null) {
                                    $fail('The selected order item is invalid for this sales order.');

                                    return;
                                }

                                if ((float) $value > $remainingQty) {
                                    $fail(sprintf('Delivered quantity cannot exceed remaining quantity (%.6f).', $remainingQty));
                                }
                            })
                            ->helperText(fn (Get $get): ?string => self::remainingQuantityHint(
                                salesOrderItemId: $get->integer('sales_order_item_id', isNullable: true),
                                salesOrderId: $get->integer('../../sales_order_id', isNullable: true),
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
                TextColumn::make('delivery_no')->searchable()->sortable(),
                TextColumn::make('delivery_date')->date()->sortable(),
                TextColumn::make('order.order_no')->label('Order No')->searchable(),
                TextColumn::make('order.customer.name')->label('Customer')->searchable(),
                TextColumn::make('warehouse.name')->label('Warehouse')->searchable(),
                TextColumn::make('status')->badge(),
            ])
            ->filters([
                Filter::make('today')
                    ->label('Today')
                    ->query(fn (Builder $query): Builder => $query->whereDate('delivery_date', today())),
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
            'index' => Pages\ListSalesDeliveries::route('/'),
            'create' => Pages\CreateSalesDelivery::route('/create'),
            'view' => Pages\ViewSalesDelivery::route('/{record}'),
        ];
    }

    /**
     * @return array<int, string>
     */
    protected static function availableSalesOrderItems(?int $salesOrderId): array
    {
        if (! $salesOrderId) {
            return [];
        }

        return SalesOrderItem::query()
            ->with(['order', 'product'])
            ->where('sales_order_id', $salesOrderId)
            ->whereHas('order', fn ($query) => $query->whereIn('status', ['approved', 'partially_delivered']))
            ->get()
            ->filter(function (SalesOrderItem $item): bool {
                return (float) $item->delivered_qty < (float) $item->quantity;
            })
            ->mapWithKeys(function (SalesOrderItem $item): array {
                $remaining = (float) $item->quantity - (float) $item->delivered_qty;
                $productName = $item->product?->name ?? 'Product #'.$item->product_id;
                $orderNo = $item->order?->order_no ?? 'SO';

                return [
                    $item->id => sprintf('%s - %s (remaining %.6f)', $orderNo, $productName, $remaining),
                ];
            })
            ->all();
    }

    protected static function remainingQuantityForOrderItem(int $salesOrderItemId, int $salesOrderId): ?float
    {
        $item = SalesOrderItem::query()
            ->whereKey($salesOrderItemId)
            ->where('sales_order_id', $salesOrderId)
            ->first();

        if (! $item) {
            return null;
        }

        return max(0.0, (float) $item->quantity - (float) $item->delivered_qty);
    }

    protected static function remainingQuantityHint(?int $salesOrderItemId, ?int $salesOrderId): ?string
    {
        if (! $salesOrderItemId || ! $salesOrderId) {
            return null;
        }

        $remaining = self::remainingQuantityForOrderItem($salesOrderItemId, $salesOrderId);

        if ($remaining === null) {
            return null;
        }

        return sprintf('Remaining quantity: %.6f', $remaining);
    }
}
