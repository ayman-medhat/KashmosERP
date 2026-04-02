<?php

namespace App\Modules\Sales\Filament\Resources;

use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\Product;
use App\Modules\MasterData\Models\Warehouse;
use App\Modules\Sales\Filament\Resources\SalesOrderResource\Pages;
use App\Modules\Sales\Models\SalesOrder;
use App\Modules\Sales\Services\SalesOrderService;
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

class SalesOrderResource extends Resource
{
    protected static ?string $model = SalesOrder::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('order_no')->label('Order No')->placeholder('Auto')->maxLength(100),
                DatePicker::make('order_date')->required()->default(now()),
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
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withSum('items as ordered_qty_sum', 'quantity')
                ->withSum('items as delivered_qty_sum', 'delivered_qty'))
            ->columns([
                TextColumn::make('order_no')->searchable()->sortable(),
                TextColumn::make('order_date')->date()->sortable(),
                TextColumn::make('customer.name')->label('Customer')->searchable(),
                TextColumn::make('warehouse.name')->label('Warehouse'),
                TextColumn::make('status')->badge(),
                TextColumn::make('delivery_progress')
                    ->label('Delivered')
                    ->state(fn (SalesOrder $record): string => self::deliveryProgressText($record))
                    ->badge(),
                TextColumn::make('grand_total')->money('EGP'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'submitted' => 'Submitted',
                        'approved' => 'Approved',
                        'partially_delivered' => 'Partially Delivered',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->recordActions([
                Action::make('submit')
                    ->color('warning')
                    ->icon('heroicon-o-paper-airplane')
                    ->requiresConfirmation()
                    ->authorize('submit')
                    ->visible(fn (SalesOrder $record): bool => $record->status === 'draft')
                    ->action(fn (SalesOrder $record) => app(SalesOrderService::class)->submit($record)),
                Action::make('approve')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->authorize('approve')
                    ->visible(fn (SalesOrder $record): bool => $record->status === 'submitted')
                    ->action(fn (SalesOrder $record) => app(SalesOrderService::class)->approve($record)),
                Action::make('cancel')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->authorize('cancel')
                    ->visible(fn (SalesOrder $record): bool => in_array($record->status, ['draft', 'submitted'], true))
                    ->action(fn (SalesOrder $record) => app(SalesOrderService::class)->cancel($record)),
                \Filament\Actions\ViewAction::make(),
            ])
            ->toolbarActions([
                \Filament\Actions\CreateAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalesOrders::route('/'),
            'create' => Pages\CreateSalesOrder::route('/create'),
            'view' => Pages\ViewSalesOrder::route('/{record}'),
        ];
    }

    public static function deliveryProgressText(SalesOrder $record): string
    {
        $ordered = self::orderedQuantity($record);
        $delivered = self::deliveredQuantity($record);

        return sprintf('%.6f / %.6f', $delivered, $ordered);
    }

    protected static function orderedQuantity(SalesOrder $record): float
    {
        if (isset($record->ordered_qty_sum)) {
            return (float) $record->ordered_qty_sum;
        }

        return (float) $record->items()->sum('quantity');
    }

    protected static function deliveredQuantity(SalesOrder $record): float
    {
        if (isset($record->delivered_qty_sum)) {
            return (float) $record->delivered_qty_sum;
        }

        return (float) $record->items()->sum('delivered_qty');
    }
}
