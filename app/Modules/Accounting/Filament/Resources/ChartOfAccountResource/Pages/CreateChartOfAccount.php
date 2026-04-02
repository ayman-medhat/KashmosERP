<?php

namespace App\Modules\Accounting\Filament\Resources\ChartOfAccountResource\Pages;

use App\Modules\Accounting\Filament\Resources\ChartOfAccountResource;
use App\Modules\Accounting\Models\ChartOfAccount;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateChartOfAccount extends CreateRecord
{
    protected static string $resource = ChartOfAccountResource::class;

    protected function handleRecordCreation(array $data): ChartOfAccount
    {
        $data['uuid'] ??= (string) Str::uuid();
        $data['created_by'] = auth()->id();

        return ChartOfAccount::query()->create($data);
    }
}

