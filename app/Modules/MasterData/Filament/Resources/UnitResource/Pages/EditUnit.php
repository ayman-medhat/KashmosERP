<?php

namespace App\Modules\MasterData\Filament\Resources\UnitResource\Pages;

use App\Modules\MasterData\Filament\Resources\UnitResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUnit extends EditRecord
{
    protected static string $resource = UnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
