<?php

namespace App\Modules\Purchasing\Filament\Resources;

use App\Modules\MasterData\Models\Product;
use App\Modules\MasterData\Models\Supplier;
use App\Modules\MasterData\Models\Warehouse;
use App\Modules\Purchasing\Filament\Resources\PurchaseOrderResource\Pages;
use App\Modules\Purchasing\Models\PurchaseOrder;
use App\Modules\Purchasing\Services\PurchaseOrderService;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = 'Purchasing';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('order_no')->label('Order No')->placeholder('Auto')->maxLength(100),
                DatePicker::make('order_date')->required()->default(now()),
                Select::make('supplier_id')
                    ->label('Supplier')
                    ->options(Supplier::query()->orderBy('id')->get()->pluck('name', 'id')->all())
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
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withSum('items as ordered_qty_sum', 'quantity')
                ->withSum('items as received_qty_sum', 'received_qty'))
            ->columns([
                TextColumn::make('order_no')->searchable()->sortable(),
                TextColumn::make('order_date')->date()->sortable(),
                TextColumn::make('supplier.name')->label('Supplier')->searchable(),
                TextColumn::make('warehouse.name')->label('Warehouse'),
                TextColumn::make('status')->badge(),
                TextColumn::make('receipt_progress')
                    ->label('Received')
                    ->state(fn (PurchaseOrder $record): string => self::receiptProgressText($record))
                    ->badge(),
                TextColumn::make('grand_total')->money('EGP'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'submitted' => 'Submitted',
                        'approved' => 'Approved',
                        'partially_received' => 'Partially Received',
                        'received' => 'Received',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->recordActions([
                Action::make('submit')
                    ->color('warning')
                    ->icon('heroicon-o-paper-airplane')
                    ->requiresConfirmation()
                    ->authorize('submit')
                    ->visible(fn (PurchaseOrder $record): bool => $record->status === 'draft')
                    ->action(fn (PurchaseOrder $record) => app(PurchaseOrderService::class)->submit($record)),
                Action::make('approve')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->authorize('approve')
                    ->visible(fn (PurchaseOrder $record): bool => $record->status === 'submitted')
                    ->action(fn (PurchaseOrder $record) => app(PurchaseOrderService::class)->approve($record)),
                Action::make('cancel')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->authorize('cancel')
                    ->visible(fn (PurchaseOrder $record): bool => in_array($record->status, ['draft', 'submitted'], true))
                    ->action(fn (PurchaseOrder $record) => app(PurchaseOrderService::class)->cancel($record)),
                \Filament\Actions\ViewAction::make(),
            ])
            ->toolbarActions([
                \Filament\Actions\CreateAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'view' => Pages\ViewPurchaseOrder::route('/{record}'),
        ];
    }

    public static function receiptProgressText(PurchaseOrder $record): string
    {
        $ordered = self::orderedQuantity($record);
        $received = self::receivedQuantity($record);

        return sprintf('%.6f / %.6f', $received, $ordered);
    }

    protected static function orderedQuantity(PurchaseOrder $record): float
    {
        if (isset($record->ordered_qty_sum)) {
            return (float) $record->ordered_qty_sum;
        }

        return (float) $record->items()->sum('quantity');
    }

    protected static function receivedQuantity(PurchaseOrder $record): float
    {
        if (isset($record->received_qty_sum)) {
            return (float) $record->received_qty_sum;
        }

        return (float) $record->items()->sum('received_qty');
    }
}
