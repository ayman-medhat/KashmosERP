<?php

namespace App\Modules\MasterData\Filament\Resources\SupplierResource\Pages;

use App\Modules\MasterData\Filament\Resources\SupplierResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSupplier extends CreateRecord
{
    protected static string $resource = SupplierResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uuid'] = (string) str()->uuid();

        return $data;
    }
}
