<?php

namespace App\Modules\Purchasing\Filament\Resources\PurchaseReceiptResource\Pages;

use App\Modules\Purchasing\Filament\Resources\PurchaseReceiptResource;
use App\Modules\Purchasing\Models\PurchaseReceipt;
use App\Modules\Purchasing\Services\PurchaseReceiptService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Gate;

class CreatePurchaseReceipt extends CreateRecord
{
    protected static string $resource = PurchaseReceiptResource::class;

    protected function handleRecordCreation(array $data): PurchaseReceipt
    {
        Gate::authorize('create', PurchaseReceipt::class);

        $items = $data['items'] ?? [];
        unset($data['items']);

        return app(PurchaseReceiptService::class)->receive($data, $items);
    }
}
