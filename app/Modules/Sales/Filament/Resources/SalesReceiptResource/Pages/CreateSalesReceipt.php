<?php

namespace App\Modules\Sales\Filament\Resources\SalesReceiptResource\Pages;

use App\Modules\Sales\Filament\Resources\SalesReceiptResource;
use App\Modules\Sales\Models\SalesReceipt;
use App\Modules\Sales\Services\SalesReceiptService;
use Filament\Resources\Pages\CreateRecord;

class CreateSalesReceipt extends CreateRecord
{
    protected static string $resource = SalesReceiptResource::class;

    protected function handleRecordCreation(array $data): SalesReceipt
    {
        return app(SalesReceiptService::class)->receive($data);
    }
}

