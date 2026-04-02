<?php

namespace App\Modules\Inventory\Filament\Resources\StockMovementResource\Pages;

use App\Modules\Inventory\Filament\Resources\StockMovementResource;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Services\StockMovementService;
use Filament\Resources\Pages\CreateRecord;

class CreateStockMovement extends CreateRecord
{
    protected static string $resource = StockMovementResource::class;

    protected function handleRecordCreation(array $data): StockMovement
    {
        return app(StockMovementService::class)->record($data);
    }
}
