<?php

namespace App\Modules\CRM\Filament\Resources\CrmLeadResource\Pages;

use App\Modules\CRM\Filament\Resources\CrmLeadResource;
use App\Modules\CRM\Models\CrmLead;
use App\Modules\CRM\Services\CrmLeadService;
use Filament\Resources\Pages\CreateRecord;

class CreateCrmLead extends CreateRecord
{
    protected static string $resource = CrmLeadResource::class;

    protected function handleRecordCreation(array $data): CrmLead
    {
        return app(CrmLeadService::class)->create($data);
    }
}

