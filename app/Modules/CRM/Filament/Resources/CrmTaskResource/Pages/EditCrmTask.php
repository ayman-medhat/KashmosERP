<?php

namespace App\Modules\CRM\Filament\Resources\CrmTaskResource\Pages;

use App\Modules\CRM\Filament\Resources\CrmTaskResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCrmTask extends EditRecord
{
    protected static string $resource = CrmTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

