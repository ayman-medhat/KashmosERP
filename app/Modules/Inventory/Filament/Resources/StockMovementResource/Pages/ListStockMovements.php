<?php

namespace App\Modules\Inventory\Filament\Resources\StockMovementResource\Pages;

use App\Modules\Inventory\Filament\Resources\StockMovementResource;
use Filament\Resources\Pages\ListRecords;

class ListStockMovements extends ListRecords
{
    protected static string $resource = StockMovementResource::class;
}
