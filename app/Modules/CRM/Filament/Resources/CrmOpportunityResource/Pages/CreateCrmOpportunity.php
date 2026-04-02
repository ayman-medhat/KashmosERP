<?php

namespace App\Modules\CRM\Filament\Resources\CrmOpportunityResource\Pages;

use App\Modules\CRM\Filament\Resources\CrmOpportunityResource;
use App\Modules\CRM\Models\CrmOpportunity;
use App\Modules\CRM\Services\CrmAssignmentService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateCrmOpportunity extends CreateRecord
{
    protected static string $resource = CrmOpportunityResource::class;

    protected function handleRecordCreation(array $data): CrmOpportunity
    {
        $data['uuid'] ??= (string) Str::uuid();
        $data['opportunity_no'] ??= 'OPP-'.str_pad((string) (CrmOpportunity::query()->count() + 1), 6, '0', STR_PAD_LEFT);
        $data['status'] ??= 'open';
        $data['created_by'] = auth()->id();
        $data['assigned_by'] = auth()->id();

        $opportunity = CrmOpportunity::query()->create($data);

        if (! $opportunity->owner_id) {
            $opportunity = app(CrmAssignmentService::class)->assignOpportunity($opportunity);
        }

        return $opportunity;
    }
}
