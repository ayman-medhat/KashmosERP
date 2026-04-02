<?php

namespace App\Modules\Sales\Filament\Resources\SalesQuotationResource\Pages;

use App\Modules\Sales\Filament\Resources\SalesQuotationResource;
use App\Modules\Sales\Models\SalesQuotation;
use App\Modules\Sales\Services\SalesQuotationService;
use Filament\Resources\Pages\CreateRecord;

class CreateSalesQuotation extends CreateRecord
{
    protected static string $resource = SalesQuotationResource::class;

    protected function handleRecordCreation(array $data): SalesQuotation
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        return app(SalesQuotationService::class)->create($data, $items);
    }
}
