<?php

namespace App\Modules\CRM\Filament\Resources\CrmActivityResource\Pages;

use App\Modules\CRM\Filament\Resources\CrmActivityResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCrmActivity extends EditRecord
{
    protected static string $resource = CrmActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

