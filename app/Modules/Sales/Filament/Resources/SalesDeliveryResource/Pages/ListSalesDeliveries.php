<?php

namespace App\Modules\Sales\Filament\Resources\SalesDeliveryResource\Pages;

use App\Modules\Sales\Filament\Resources\SalesDeliveryResource;
use App\Modules\Sales\Models\SalesDelivery;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Pages\ListRecords;

class ListSalesDeliveries extends ListRecords
{
    protected static string $resource = SalesDeliveryResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge((string) SalesDelivery::query()->count()),
            'today' => Tab::make('Today')
                ->badge((string) SalesDelivery::query()->whereDate('delivery_date', today())->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereDate('delivery_date', today())),
        ];
    }
}
