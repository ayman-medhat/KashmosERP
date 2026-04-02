<?php

namespace App\Core\Filament\Widgets;

use App\Core\Models\AuditLog;
use App\Core\Models\Permission;
use App\Core\Models\Role;
use App\Core\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class KashmosOverviewWidget extends StatsOverviewWidget
{
    protected ?string $heading = 'Kashmos Overview';

    protected function getStats(): array
    {
        return [
            Stat::make('Users', (string) User::query()->count())
                ->description('Active ERP users')
                ->color('primary'),
            Stat::make('Roles', (string) Role::query()->count())
                ->description('Security roles')
                ->color('success'),
            Stat::make('Permissions', (string) Permission::query()->count())
                ->description('Granted capabilities')
                ->color('warning'),
            Stat::make('Audit Logs', (string) AuditLog::query()->count())
                ->description('Sensitive activity entries')
                ->color('gray'),
        ];
    }
}
