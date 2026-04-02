<?php

namespace App\Modules\MasterData\Filament\Resources;

use App\Core\Services\DashboardMetricsService;
use App\Modules\MasterData\Filament\Resources\ProductResource\Pages;
use App\Modules\MasterData\Models\Product;
use App\Modules\MasterData\Models\ProductCategory;
use App\Modules\MasterData\Models\Tax;
use App\Modules\MasterData\Models\Unit;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('sku')->required()->maxLength(100),
                TextInput::make('name_translations.en')->label('Name (English)')->required(),
                TextInput::make('name_translations.ar')->label('Name (Arabic)')->required(),
                TextInput::make('description_translations.en')->label('Description (English)'),
                TextInput::make('description_translations.ar')->label('Description (Arabic)'),
                Select::make('product_category_id')
                    ->label('Category')
                    ->options(ProductCategory::query()->orderBy('id')->get()->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload(),
                Select::make('unit_id')
                    ->label('Unit')
                    ->options(Unit::query()->pluck('code', 'id'))
                    ->required()
                    ->searchable()
                    ->preload(),
                Select::make('tax_id')
                    ->label('Tax')
                    ->options(Tax::query()->pluck('code', 'id'))
                    ->searchable()
                    ->preload(),
                TextInput::make('cost_price')->numeric()->required(),
                TextInput::make('sale_price')->numeric()->required(),
                TextInput::make('opening_stock')->numeric()->default(0)->required(),
                TextInput::make('reorder_level')->numeric()->default(0)->required(),
                Toggle::make('track_stock')->default(true)->required(),
                Toggle::make('is_active')->default(true)->required(),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sku')->searchable()->sortable(),
                TextColumn::make('name')->label('Name')->searchable(),
                TextColumn::make('category.name')->label('Category'),
                TextColumn::make('unit.code')->label('Unit'),
                TextColumn::make('sale_price')->money('EGP')->sortable(),
                TextColumn::make('opening_stock')->sortable(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                Filter::make('low_stock')
                    ->label('Low Stock')
                    ->query(fn (Builder $query): Builder => $query->whereIn(
                        'products.id',
                        app(DashboardMetricsService::class)->lowStockProductIds(),
                    )),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                \Filament\Actions\CreateAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
