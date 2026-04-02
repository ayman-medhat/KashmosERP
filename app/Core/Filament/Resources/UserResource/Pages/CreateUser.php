<?php

namespace App\Core\Filament\Resources\UserResource\Pages;

use App\Core\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
