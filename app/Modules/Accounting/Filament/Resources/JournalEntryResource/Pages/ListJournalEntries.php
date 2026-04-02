<?php

namespace App\Modules\Accounting\Filament\Resources\JournalEntryResource\Pages;

use App\Modules\Accounting\Filament\Resources\JournalEntryResource;
use Filament\Resources\Pages\ListRecords;

class ListJournalEntries extends ListRecords
{
    protected static string $resource = JournalEntryResource::class;
}

