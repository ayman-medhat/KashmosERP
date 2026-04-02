<?php

namespace App\Modules\CRM\Filament\Resources\CrmPipelineStageResource\Pages;

use App\Modules\CRM\Filament\Resources\CrmPipelineStageResource;
use App\Modules\CRM\Models\CrmPipelineStage;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateCrmPipelineStage extends CreateRecord
{
    protected static string $resource = CrmPipelineStageResource::class;

    protected function handleRecordCreation(array $data): CrmPipelineStage
    {
        $data['uuid'] ??= (string) Str::uuid();
        $data['created_by'] = auth()->id();

        return CrmPipelineStage::query()->create($data);
    }
}

