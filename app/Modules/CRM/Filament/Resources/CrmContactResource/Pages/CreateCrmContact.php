<?php

namespace App\Modules\CRM\Filament\Resources\CrmContactResource\Pages;

use App\Modules\CRM\Filament\Resources\CrmContactResource;
use App\Modules\CRM\Models\CrmContact;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateCrmContact extends CreateRecord
{
    protected static string $resource = CrmContactResource::class;

    protected function handleRecordCreation(array $data): CrmContact
    {
        $data['uuid'] ??= (string) Str::uuid();
        $data['created_by'] = auth()->id();
        $data['assigned_by'] = auth()->id();

        return CrmContact::query()->create($data);
    }
}

