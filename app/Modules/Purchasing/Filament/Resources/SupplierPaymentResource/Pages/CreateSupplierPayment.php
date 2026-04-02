<?php

namespace App\Modules\Purchasing\Filament\Resources\SupplierPaymentResource\Pages;

use App\Modules\Purchasing\Filament\Resources\SupplierPaymentResource;
use App\Modules\Purchasing\Models\SupplierPayment;
use App\Modules\Purchasing\Services\SupplierPaymentService;
use Filament\Resources\Pages\CreateRecord;

class CreateSupplierPayment extends CreateRecord
{
    protected static string $resource = SupplierPaymentResource::class;

    protected function handleRecordCreation(array $data): SupplierPayment
    {
        return app(SupplierPaymentService::class)->pay($data);
    }
}

