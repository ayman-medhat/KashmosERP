<?php

namespace App\Modules\CRM\Filament\Resources\CrmAssignmentRuleResource\Pages;

use App\Modules\CRM\Filament\Resources\CrmAssignmentRuleResource;
use App\Modules\CRM\Models\CrmAssignmentRule;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateCrmAssignmentRule extends CreateRecord
{
    protected static string $resource = CrmAssignmentRuleResource::class;

    protected function handleRecordCreation(array $data): CrmAssignmentRule
    {
        $data['uuid'] = (string) Str::uuid();
        $data['created_by'] = auth()->id();
        $data['conditions'] = $this->sanitizeConditions($data['conditions'] ?? []);

        return CrmAssignmentRule::query()->create($data);
    }

    /**
     * @param array<mixed> $conditions
     * @return array<string, mixed>
     */
    private function sanitizeConditions(array $conditions): array
    {
        return collect($conditions)
            ->filter(fn (mixed $value, mixed $key): bool => filled($key) && filled($value))
            ->mapWithKeys(fn (mixed $value, mixed $key): array => [trim((string) $key) => is_string($value) ? trim($value) : $value])
            ->all();
    }
}

