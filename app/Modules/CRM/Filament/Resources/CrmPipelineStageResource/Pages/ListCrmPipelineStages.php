<?php

namespace App\Modules\CRM\Filament\Resources\CrmPipelineStageResource\Pages;

use App\Modules\CRM\Filament\Resources\CrmPipelineStageResource;
use Filament\Resources\Pages\ListRecords;

class ListCrmPipelineStages extends ListRecords
{
    protected static string $resource = CrmPipelineStageResource::class;
}

