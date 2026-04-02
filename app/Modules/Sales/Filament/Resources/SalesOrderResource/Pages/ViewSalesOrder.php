<?php

namespace App\Modules\Sales\Filament\Resources\SalesOrderResource\Pages;

use App\Modules\Sales\Filament\Resources\SalesOrderResource;
use App\Modules\Sales\Models\SalesOrder;
use Filament\Resources\Pages\ViewRecord;

class ViewSalesOrder extends ViewRecord
{
    protected static string $resource = SalesOrderResource::class;

    public function getSubheading(): ?string
    {
        /** @var SalesOrder $record */
        $record = $this->getRecord();

        return 'Delivered: '.SalesOrderResource::deliveryProgressText($record);
    }
}
