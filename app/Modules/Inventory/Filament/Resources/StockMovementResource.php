<?php

namespace App\Modules\Inventory\Filament\Resources;

use App\Modules\Inventory\Filament\Resources\StockMovementResource\Pages;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\MasterData\Models\Product;
use App\Modules\MasterData\Models\Warehouse;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrows-up-down';

    protected static string|\UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->label('Product')
                    ->options(Product::query()->orderBy('id')->get()->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('warehouse_id')
                    ->label('Warehouse')
                    ->options(Warehouse::query()->orderBy('id')->get()->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('movement_type')
                    ->options([
                        'opening' => 'Opening',
                        'adjustment' => 'Adjustment',
                        'purchase_order' => 'Purchase Order',
                        'sales_order' => 'Sales Order',
                    ])
                    ->required(),
                TextInput::make('reference_no')->maxLength(100),
                TextInput::make('quantity')->numeric()->required(),
                TextInput::make('unit_cost')->numeric(),
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
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('product.name')->label('Product')->searchable(),
                TextColumn::make('warehouse.name')->label('Warehouse'),
                TextColumn::make('movement_type')->badge(),
                TextColumn::make('reference_no')->searchable(),
                TextColumn::make('quantity')->sortable(),
                TextColumn::make('balance_after')->label('Balance After')->sortable(),
                TextColumn::make('creator.email')->label('Created By'),
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
            'index' => Pages\ListStockMovements::route('/'),
            'create' => Pages\CreateStockMovement::route('/create'),
            'view' => Pages\ViewStockMovement::route('/{record}'),
        ];
    }
}
