<?php

namespace App\Modules\MasterData\Filament\Resources\TaxResource\Pages;

use App\Modules\MasterData\Filament\Resources\TaxResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTax extends EditRecord
{
    protected static string $resource = TaxResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
