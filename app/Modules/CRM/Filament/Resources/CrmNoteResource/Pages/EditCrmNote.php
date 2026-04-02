<?php

namespace App\Modules\CRM\Filament\Resources\CrmNoteResource\Pages;

use App\Modules\CRM\Filament\Resources\CrmNoteResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCrmNote extends EditRecord
{
    protected static string $resource = CrmNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

