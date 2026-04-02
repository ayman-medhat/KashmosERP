<?php

namespace App\Modules\Purchasing\Filament\Resources\PurchaseOrderResource\Pages;

use App\Modules\Purchasing\Filament\Resources\PurchaseOrderResource;
use App\Modules\Purchasing\Models\PurchaseOrder;
use Filament\Resources\Pages\ViewRecord;

class ViewPurchaseOrder extends ViewRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    public function getSubheading(): ?string
    {
        /** @var PurchaseOrder $record */
        $record = $this->getRecord();

        return 'Received: '.PurchaseOrderResource::receiptProgressText($record);
    }
}
