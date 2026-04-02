<?php

namespace App\Modules\MasterData\Filament\Resources\ProductResource\Pages;

use App\Core\Services\DashboardMetricsService;
use App\Modules\MasterData\Filament\Resources\ProductResource;
use App\Modules\MasterData\Models\Product;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    public function getTabs(): array
    {
        $lowStockProductIds = app(DashboardMetricsService::class)->lowStockProductIds();

        return [
            'all' => Tab::make('All')
                ->badge((string) Product::query()->count()),
            'low_stock' => Tab::make('Low Stock')
                ->badge((string) count($lowStockProductIds))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereIn('products.id', $lowStockProductIds)),
        ];
    }
}
