<?php

namespace App\Modules\CRM\Filament\Resources\CrmLeadSourceResource\Pages;

use App\Modules\CRM\Filament\Resources\CrmLeadSourceResource;
use App\Modules\CRM\Models\CrmLeadSource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateCrmLeadSource extends CreateRecord
{
    protected static string $resource = CrmLeadSourceResource::class;

    protected function handleRecordCreation(array $data): CrmLeadSource
    {
        $data['uuid'] ??= (string) Str::uuid();
        $data['created_by'] = auth()->id();

        return CrmLeadSource::query()->create($data);
    }
}

