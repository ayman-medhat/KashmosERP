<?php

namespace App\Core\Filament\Resources\PermissionResource\Pages;

use App\Core\Filament\Resources\PermissionResource;
use Filament\Resources\Pages\ListRecords;

class ListPermissions extends ListRecords
{
    protected static string $resource = PermissionResource::class;
}
