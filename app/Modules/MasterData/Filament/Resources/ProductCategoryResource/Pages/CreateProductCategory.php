<?php

namespace App\Modules\MasterData\Filament\Resources\ProductCategoryResource\Pages;

use App\Modules\MasterData\Filament\Resources\ProductCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProductCategory extends CreateRecord
{
    protected static string $resource = ProductCategoryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uuid'] = (string) str()->uuid();

        return $data;
    }
}
