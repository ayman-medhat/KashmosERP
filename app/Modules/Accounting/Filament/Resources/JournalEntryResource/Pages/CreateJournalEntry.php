<?php

namespace App\Modules\Accounting\Filament\Resources\JournalEntryResource\Pages;

use App\Modules\Accounting\Filament\Resources\JournalEntryResource;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Accounting\Services\JournalEntryService;
use Filament\Resources\Pages\CreateRecord;

class CreateJournalEntry extends CreateRecord
{
    protected static string $resource = JournalEntryResource::class;

    protected function handleRecordCreation(array $data): JournalEntry
    {
        $lines = $data['lines'] ?? [];
        unset($data['lines']);

        return app(JournalEntryService::class)->create($data, $lines);
    }
}

