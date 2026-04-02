<?php

namespace App\Modules\Sales\Filament\Resources\SalesOrderResource\Pages;

use App\Modules\Sales\Filament\Resources\SalesOrderResource;
use App\Modules\Sales\Models\SalesOrder;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Pages\ListRecords;

class ListSalesOrders extends ListRecords
{
    protected static string $resource = SalesOrderResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge((string) SalesOrder::query()->count()),
            'open_approved' => Tab::make('Open Approved')
                ->badge((string) SalesOrder::query()->where('status', 'approved')->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'approved')),
            'partially_delivered' => Tab::make('Partially Delivered')
                ->badge((string) SalesOrder::query()->where('status', 'partially_delivered')->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'partially_delivered')),
        ];
    }
}
