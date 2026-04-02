<?php

namespace App\Modules\Purchasing\Filament\Resources\PurchaseReceiptResource\Pages;

use App\Modules\Purchasing\Filament\Resources\PurchaseReceiptResource;
use App\Modules\Purchasing\Models\PurchaseReceipt;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Pages\ListRecords;

class ListPurchaseReceipts extends ListRecords
{
    protected static string $resource = PurchaseReceiptResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge((string) PurchaseReceipt::query()->count()),
            'today' => Tab::make('Today')
                ->badge((string) PurchaseReceipt::query()->whereDate('received_date', today())->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereDate('received_date', today())),
        ];
    }
}
