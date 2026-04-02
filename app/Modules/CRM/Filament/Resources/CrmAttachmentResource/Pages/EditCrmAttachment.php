<?php

namespace App\Modules\CRM\Filament\Resources\CrmAttachmentResource\Pages;

use App\Modules\CRM\Filament\Resources\CrmAttachmentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCrmAttachment extends EditRecord
{
    protected static string $resource = CrmAttachmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

