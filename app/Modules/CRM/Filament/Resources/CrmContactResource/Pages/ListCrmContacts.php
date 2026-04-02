<?php

namespace App\Modules\CRM\Filament\Resources\CrmContactResource\Pages;

use App\Modules\CRM\Filament\Resources\CrmContactResource;
use Filament\Resources\Pages\ListRecords;

class ListCrmContacts extends ListRecords
{
    protected static string $resource = CrmContactResource::class;
}

