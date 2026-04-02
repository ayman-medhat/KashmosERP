<?php

namespace App\Modules\CRM\Filament\Resources\CrmActivityResource\Pages;

use App\Modules\CRM\Filament\Resources\CrmActivityResource;
use App\Modules\CRM\Models\CrmActivity;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateCrmActivity extends CreateRecord
{
    protected static string $resource = CrmActivityResource::class;

    protected function handleRecordCreation(array $data): CrmActivity
    {
        $data['uuid'] ??= (string) Str::uuid();
        $data['created_by'] = auth()->id();

        return CrmActivity::query()->create($data);
    }
}

