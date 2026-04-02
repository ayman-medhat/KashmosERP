<?php

namespace App\Core\Filament\Resources\AuditLogResource\Pages;

use App\Core\Filament\Resources\AuditLogResource;
use Filament\Resources\Pages\ListRecords;

class ListAuditLogs extends ListRecords
{
    protected static string $resource = AuditLogResource::class;
}
