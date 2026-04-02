<?php

namespace App\Providers\Filament;

use App\Core\Filament\Pages\CompanyProfilePage;
use App\Core\Filament\Pages\SettingsPage;
use App\Core\Filament\Resources\AuditLogResource;
use App\Core\Filament\Resources\PermissionResource;
use App\Core\Filament\Resources\RoleResource;
use App\Core\Filament\Resources\UserResource;
use App\Core\Filament\Widgets\KashmosBacklogQualityWidget;
use App\Core\Filament\Widgets\KashmosOperationsWidget;
use App\Core\Filament\Widgets\KashmosOperationsTrendWidget;
use App\Core\Support\KashmosTheme;
use App\Modules\CRM\Filament\Pages\CrmDashboardPage;
use App\Modules\CRM\Filament\Pages\CrmPipelineBoardPage;
use App\Modules\CRM\Filament\Pages\CrmReportsPage;
use App\Modules\CRM\Filament\Pages\CrmTimelinePage;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('Kashmos ERP')
            ->sidebarCollapsibleOnDesktop()
            ->globalSearch()
            ->colors([
                'primary' => Color::Slate,
            ])
            ->discoverResources(in: app_path('Core/Filament/Resources'), for: 'App\Core\Filament\Resources')
            ->discoverResources(in: app_path('Modules'), for: 'App\Modules')
            ->discoverPages(in: app_path('Core/Filament/Pages'), for: 'App\Core\Filament\Pages')
            ->pages([
                Dashboard::class,
                SettingsPage::class,
                CompanyProfilePage::class,
                CrmDashboardPage::class,
                CrmReportsPage::class,
                CrmPipelineBoardPage::class,
                CrmTimelinePage::class,
            ])
            ->discoverWidgets(in: app_path('Core/Filament/Widgets'), for: 'App\Core\Filament\Widgets')
            ->widgets([
                KashmosTheme::dashboardWidget(),
                KashmosOperationsWidget::class,
                KashmosOperationsTrendWidget::class,
                KashmosBacklogQualityWidget::class,
            ])
            ->resources([
                UserResource::class,
                RoleResource::class,
                PermissionResource::class,
                AuditLogResource::class,
            ])
            ->navigationGroups([
                NavigationGroup::make()->label(__('core.navigation.administration')),
                NavigationGroup::make()->label(__('core.navigation.master_data')),
                NavigationGroup::make()->label(__('core.navigation.inventory')),
                NavigationGroup::make()->label(__('core.navigation.sales')),
                NavigationGroup::make()->label(__('core.navigation.crm')),
                NavigationGroup::make()->label(__('core.navigation.purchasing')),
                NavigationGroup::make()->label(__('core.navigation.accounting')),
                NavigationGroup::make()->label(__('core.navigation.system')),
                NavigationGroup::make()->label(__('core.navigation.monitoring')),
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => view('filament.components.kashmos-context')->render(),
            )
            ->renderHook(
                PanelsRenderHook::FOOTER,
                fn (): string => view('filament.components.kashmos-footer')->render(),
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
