<?php

namespace App\Modules\MasterData\Filament\Resources\UnitResource\Pages;

use App\Modules\MasterData\Filament\Resources\UnitResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUnit extends CreateRecord
{
    protected static string $resource = UnitResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uuid'] = (string) str()->uuid();

        return $data;
    }
}
