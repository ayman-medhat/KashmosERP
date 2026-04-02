<?php

namespace App\Modules\Purchasing\Filament\Resources\PurchaseOrderResource\Pages;

use App\Modules\Purchasing\Filament\Resources\PurchaseOrderResource;
use App\Modules\Purchasing\Models\PurchaseOrder;
use App\Modules\Purchasing\Services\PurchaseOrderService;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchaseOrder extends CreateRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function handleRecordCreation(array $data): PurchaseOrder
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        return app(PurchaseOrderService::class)->create($data, $items);
    }
}
