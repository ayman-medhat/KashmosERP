<?php

namespace App\Modules\CRM\Filament\Resources\CrmAccountResource\Pages;

use App\Modules\CRM\Filament\Resources\CrmAccountResource;
use App\Modules\CRM\Models\CrmAccount;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateCrmAccount extends CreateRecord
{
    protected static string $resource = CrmAccountResource::class;

    protected function handleRecordCreation(array $data): CrmAccount
    {
        $data['uuid'] ??= (string) Str::uuid();
        $data['created_by'] = auth()->id();
        $data['assigned_by'] = auth()->id();

        return CrmAccount::query()->create($data);
    }
}

