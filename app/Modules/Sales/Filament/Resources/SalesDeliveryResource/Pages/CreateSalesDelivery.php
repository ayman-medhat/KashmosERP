<?php

namespace App\Modules\Sales\Filament\Resources\SalesDeliveryResource\Pages;

use App\Modules\Sales\Filament\Resources\SalesDeliveryResource;
use App\Modules\Sales\Models\SalesDelivery;
use App\Modules\Sales\Services\SalesDeliveryService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Gate;

class CreateSalesDelivery extends CreateRecord
{
    protected static string $resource = SalesDeliveryResource::class;

    protected function handleRecordCreation(array $data): SalesDelivery
    {
        Gate::authorize('create', SalesDelivery::class);

        $items = $data['items'] ?? [];
        unset($data['items']);

        return app(SalesDeliveryService::class)->deliver($data, $items);
    }
}
