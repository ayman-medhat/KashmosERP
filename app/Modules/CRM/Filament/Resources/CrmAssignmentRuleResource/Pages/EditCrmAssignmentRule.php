<?php

namespace App\Modules\CRM\Filament\Resources\CrmAssignmentRuleResource\Pages;

use App\Modules\CRM\Filament\Resources\CrmAssignmentRuleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCrmAssignmentRule extends EditRecord
{
    protected static string $resource = CrmAssignmentRuleResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['conditions'] = $this->sanitizeConditions($data['conditions'] ?? []);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
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

