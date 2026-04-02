<?php

namespace App\Modules\MasterData\Filament\Resources\WarehouseResource\Pages;

use App\Modules\MasterData\Filament\Resources\WarehouseResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWarehouse extends CreateRecord
{
    protected static string $resource = WarehouseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uuid'] = (string) str()->uuid();

        return $data;
    }
}
