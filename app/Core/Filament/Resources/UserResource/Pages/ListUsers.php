<?php

namespace App\Core\Filament\Resources\UserResource\Pages;

use App\Core\Filament\Resources\UserResource;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;
}
