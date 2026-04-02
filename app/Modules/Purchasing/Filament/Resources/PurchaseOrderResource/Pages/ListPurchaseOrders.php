<?php

namespace App\Modules\Purchasing\Filament\Resources\PurchaseOrderResource\Pages;

use App\Modules\Purchasing\Filament\Resources\PurchaseOrderResource;
use App\Modules\Purchasing\Models\PurchaseOrder;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Pages\ListRecords;

class ListPurchaseOrders extends ListRecords
{
    protected static string $resource = PurchaseOrderResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge((string) PurchaseOrder::query()->count()),
            'open_approved' => Tab::make('Open Approved')
                ->badge((string) PurchaseOrder::query()->where('status', 'approved')->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'approved')),
            'partially_received' => Tab::make('Partially Received')
                ->badge((string) PurchaseOrder::query()->where('status', 'partially_received')->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'partially_received')),
        ];
    }
}
