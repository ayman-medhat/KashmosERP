<?php

namespace App\Modules\Sales\Filament\Resources\SalesInvoiceResource\Pages;

use App\Modules\Sales\Filament\Resources\SalesInvoiceResource;
use App\Modules\Sales\Models\SalesInvoice;
use App\Modules\Sales\Services\SalesInvoiceService;
use Filament\Resources\Pages\CreateRecord;

class CreateSalesInvoice extends CreateRecord
{
    protected static string $resource = SalesInvoiceResource::class;

    protected function handleRecordCreation(array $data): SalesInvoice
    {
        return app(SalesInvoiceService::class)->createFromDelivery($data);
    }
}

