<?php

namespace App\Modules\Purchasing\Filament\Resources\SupplierPaymentResource\Pages;

use App\Modules\Purchasing\Filament\Resources\SupplierPaymentResource;
use Filament\Resources\Pages\ListRecords;

class ListSupplierPayments extends ListRecords
{
    protected static string $resource = SupplierPaymentResource::class;
}

