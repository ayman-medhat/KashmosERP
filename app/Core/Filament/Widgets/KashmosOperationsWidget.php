<?php

namespace App\Core\Filament\Widgets;

use App\Core\Services\DashboardMetricsService;
use App\Modules\MasterData\Filament\Resources\ProductResource;
use App\Modules\Purchasing\Filament\Resources\PurchaseOrderResource;
use App\Modules\Purchasing\Filament\Resources\PurchaseReceiptResource;
use App\Modules\Sales\Filament\Resources\SalesDeliveryResource;
use App\Modules\Sales\Filament\Resources\SalesOrderResource;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class KashmosOperationsWidget extends StatsOverviewWidget
{
    protected ?string $heading = 'Operations KPIs';

    protected function getStats(): array
    {
        $metrics = app(DashboardMetricsService::class)->summary();

        return [
            Stat::make('Today Sales Deliveries', (string) $metrics['today_sales_deliveries'])
                ->description('Confirmed deliveries today')
                ->color('primary')
                ->url(SalesDeliveryResource::getUrl('index', [
                    'tableFilters' => [
                        'today' => [
                            'isActive' => true,
                        ],
                    ],
                ])),
            Stat::make('Today Purchase Receipts', (string) $metrics['today_purchase_receipts'])
                ->description('Confirmed receipts today')
                ->color('primary')
                ->url(PurchaseReceiptResource::getUrl('index', [
                    'tableFilters' => [
                        'today' => [
                            'isActive' => true,
                        ],
                    ],
                ])),
            Stat::make('Open Approved Sales Orders', (string) $metrics['open_approved_sales_orders'])
                ->description('Awaiting delivery')
                ->color('warning')
                ->url(SalesOrderResource::getUrl('index', [
                    'tableFilters' => [
                        'status' => [
                            'value' => 'approved',
                        ],
                    ],
                ])),
            Stat::make('Open Approved Purchase Orders', (string) $metrics['open_approved_purchase_orders'])
                ->description('Awaiting receipt')
                ->color('warning')
                ->url(PurchaseOrderResource::getUrl('index', [
                    'tableFilters' => [
                        'status' => [
                            'value' => 'approved',
                        ],
                    ],
                ])),
            Stat::make('Partially Fulfilled Sales Orders', (string) $metrics['partially_delivered_sales_orders'])
                ->description('Partially delivered orders')
                ->color('success')
                ->url(SalesOrderResource::getUrl('index', [
                    'tableFilters' => [
                        'status' => [
                            'value' => 'partially_delivered',
                        ],
                    ],
                ])),
            Stat::make('Partially Fulfilled Purchase Orders', (string) $metrics['partially_received_purchase_orders'])
                ->description('Partially received orders')
                ->color('success')
                ->url(PurchaseOrderResource::getUrl('index', [
                    'tableFilters' => [
                        'status' => [
                            'value' => 'partially_received',
                        ],
                    ],
                ])),
            Stat::make('Low Stock Alerts', (string) $metrics['low_stock_alert_products'])
                ->description('Products at or below reorder level')
                ->color($metrics['low_stock_alert_products'] > 0 ? 'danger' : 'gray')
                ->url(ProductResource::getUrl('index', [
                    'tableFilters' => [
                        'low_stock' => [
                            'isActive' => true,
                        ],
                    ],
                ])),
        ];
    }
}
