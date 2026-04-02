<?php

namespace App\Modules\Sales\Filament\Resources\SalesOrderResource\Pages;

use App\Modules\Sales\Filament\Resources\SalesOrderResource;
use App\Modules\Sales\Models\SalesOrder;
use App\Modules\Sales\Services\SalesOrderService;
use Filament\Resources\Pages\CreateRecord;

class CreateSalesOrder extends CreateRecord
{
    protected static string $resource = SalesOrderResource::class;

    protected function handleRecordCreation(array $data): SalesOrder
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        return app(SalesOrderService::class)->create($data, $items);
    }
}
