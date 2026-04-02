<?php

namespace App\Modules\CRM\Filament\Resources\CrmOpportunityResource\Pages;

use App\Modules\CRM\Filament\Resources\CrmOpportunityResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCrmOpportunity extends EditRecord
{
    protected static string $resource = CrmOpportunityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

