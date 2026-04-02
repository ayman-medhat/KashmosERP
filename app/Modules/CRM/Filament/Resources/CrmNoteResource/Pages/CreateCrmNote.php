<?php

namespace App\Modules\CRM\Filament\Resources\CrmNoteResource\Pages;

use App\Modules\CRM\Filament\Resources\CrmNoteResource;
use App\Modules\CRM\Models\CrmNote;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateCrmNote extends CreateRecord
{
    protected static string $resource = CrmNoteResource::class;

    protected function handleRecordCreation(array $data): CrmNote
    {
        $data['uuid'] ??= (string) Str::uuid();
        $data['created_by'] = auth()->id();

        return CrmNote::query()->create($data);
    }
}

