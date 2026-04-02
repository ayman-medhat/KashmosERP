<?php

namespace App\Modules\CRM\Filament\Pages;

use App\Modules\CRM\Filament\Widgets\CrmActivityHealthWidget;
use App\Modules\CRM\Filament\Widgets\CrmOwnerPerformanceWidget;
use App\Modules\CRM\Filament\Widgets\CrmPipelineSummaryWidget;
use App\Modules\CRM\Filament\Widgets\CrmSourcePerformanceWidget;
use Filament\Pages\Dashboard;

class CrmDashboardPage extends Dashboard
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?int $navigationSort = 0;

    protected static string $routePath = '/crm-dashboard';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('crm.view_reports') ?? false;
    }

    public static function getNavigationGroup(): ?string
    {
        return __('core.navigation.crm');
    }

    public static function getNavigationLabel(): string
    {
        return __('crm.pages.dashboard.navigation');
    }

    public function getTitle(): string
    {
        return __('crm.pages.dashboard.title');
    }

    public function getWidgets(): array
    {
        return [
            CrmPipelineSummaryWidget::class,
            CrmActivityHealthWidget::class,
            CrmSourcePerformanceWidget::class,
            CrmOwnerPerformanceWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 2;
    }
}
