<?php

namespace App\Modules\Purchasing\Filament\Resources\SupplierBillResource\Pages;

use App\Modules\Purchasing\Filament\Resources\SupplierBillResource;
use App\Modules\Purchasing\Models\SupplierBill;
use App\Modules\Purchasing\Services\SupplierBillService;
use Filament\Resources\Pages\CreateRecord;

class CreateSupplierBill extends CreateRecord
{
    protected static string $resource = SupplierBillResource::class;

    protected function handleRecordCreation(array $data): SupplierBill
    {
        return app(SupplierBillService::class)->createFromReceipt($data);
    }
}

