<?php

namespace App\Modules\MasterData\Filament\Resources\TaxResource\Pages;

use App\Modules\MasterData\Filament\Resources\TaxResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTax extends CreateRecord
{
    protected static string $resource = TaxResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uuid'] = (string) str()->uuid();

        return $data;
    }
}
