<?php

namespace App\Modules\CRM\Filament\Resources\CrmTaskResource\Pages;

use App\Modules\CRM\Filament\Resources\CrmTaskResource;
use App\Modules\CRM\Models\CrmTask;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateCrmTask extends CreateRecord
{
    protected static string $resource = CrmTaskResource::class;

    protected function handleRecordCreation(array $data): CrmTask
    {
        $data['uuid'] ??= (string) Str::uuid();
        $data['created_by'] = auth()->id();

        return CrmTask::query()->create($data);
    }
}

