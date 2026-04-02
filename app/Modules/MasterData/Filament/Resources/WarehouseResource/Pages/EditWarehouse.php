<?php

namespace App\Modules\MasterData\Filament\Resources\WarehouseResource\Pages;

use App\Modules\MasterData\Filament\Resources\WarehouseResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWarehouse extends EditRecord
{
    protected static string $resource = WarehouseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
