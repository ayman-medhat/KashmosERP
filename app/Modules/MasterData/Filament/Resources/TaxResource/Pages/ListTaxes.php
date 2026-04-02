<?php

namespace App\Modules\MasterData\Filament\Resources\TaxResource\Pages;

use App\Modules\MasterData\Filament\Resources\TaxResource;
use Filament\Resources\Pages\ListRecords;

class ListTaxes extends ListRecords
{
    protected static string $resource = TaxResource::class;
}
