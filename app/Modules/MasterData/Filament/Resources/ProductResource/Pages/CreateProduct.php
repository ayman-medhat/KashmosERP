<?php

namespace App\Modules\MasterData\Filament\Resources\ProductResource\Pages;

use App\Modules\MasterData\Filament\Resources\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uuid'] = (string) str()->uuid();

        return $data;
    }
}
