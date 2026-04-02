<?php

namespace Tests\Feature\Core;

use App\Core\Services\DashboardMetricsService;
use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\Product;
use App\Modules\MasterData\Models\ProductCategory;
use App\Modules\MasterData\Models\Supplier;
use App\Modules\MasterData\Models\Tax;
use App\Modules\MasterData\Models\Unit;
use App\Modules\MasterData\Models\Warehouse;
use App\Modules\Purchasing\Services\PurchaseOrderService;
use App\Modules\Purchasing\Services\PurchaseReceiptService;
use App\Modules\Sales\Services\SalesDeliveryService;
use App\Modules\Sales\Services\SalesOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardMetricsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_metrics_summary_returns_expected_operational_kpis(): void
    {
        $this->seed();

        $metricsService = app(DashboardMetricsService::class);
        $salesOrderService = app(SalesOrderService::class);
        $salesDeliveryService = app(SalesDeliveryService::class);
        $purchaseOrderService = app(PurchaseOrderService::class);
        $purchaseReceiptService = app(PurchaseReceiptService::class);

        $customer = Customer::query()->where('code', 'CUST-001')->firstOrFail();
        $supplier = Supplier::query()->where('code', 'SUP-001')->firstOrFail();
        $warehouse = Warehouse::query()->where('code', 'MAIN')->firstOrFail();
        $product = Product::query()->where('sku', 'PROD-001')->firstOrFail();

        $openSalesOrder = $salesOrderService->approve($salesOrderService->submit($salesOrderService->create([
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->toDateString(),
        ], [[
            'product_id' => $product->id,
            'quantity' => 5,
            'unit_price' => 15,
            'tax_rate' => 0,
        ]])));

        $partialSalesOrder = $salesOrderService->approve($salesOrderService->submit($salesOrderService->create([
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->toDateString(),
        ], [[
            'product_id' => $product->id,
            'quantity' => 8,
            'unit_price' => 15,
            'tax_rate' => 0,
        ]])));

        $salesDeliveryService->deliver([
            'sales_order_id' => $partialSalesOrder->id,
            'delivery_date' => now()->toDateString(),
        ], [[
            'sales_order_item_id' => $partialSalesOrder->items()->firstOrFail()->id,
            'delivered_qty' => 3,
        ]]);

        $openPurchaseOrder = $purchaseOrderService->approve($purchaseOrderService->submit($purchaseOrderService->create([
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->toDateString(),
        ], [[
            'product_id' => $product->id,
            'quantity' => 6,
            'unit_price' => 11,
            'tax_rate' => 0,
        ]])));

        $partialPurchaseOrder = $purchaseOrderService->approve($purchaseOrderService->submit($purchaseOrderService->create([
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->toDateString(),
        ], [[
            'product_id' => $product->id,
            'quantity' => 9,
            'unit_price' => 11,
            'tax_rate' => 0,
        ]])));

        $purchaseReceiptService->receive([
            'purchase_order_id' => $partialPurchaseOrder->id,
            'received_date' => now()->toDateString(),
        ], [[
            'purchase_order_item_id' => $partialPurchaseOrder->items()->firstOrFail()->id,
            'received_qty' => 2,
        ]]);

        $unit = Unit::query()->where('code', 'PCS')->firstOrFail();
        $category = ProductCategory::query()->where('uuid', '11111111-1111-1111-1111-111111111111')->firstOrFail();
        $tax = Tax::query()->where('code', 'VAT14')->firstOrFail();

        Product::query()->create([
            'uuid' => (string) str()->uuid(),
            'sku' => 'PROD-LOW-001',
            'name_translations' => ['en' => 'Low Stock Product', 'ar' => 'منتج منخفض المخزون'],
            'description_translations' => ['en' => 'Low stock test item', 'ar' => 'عنصر اختبار منخفض المخزون'],
            'product_category_id' => $category->id,
            'unit_id' => $unit->id,
            'tax_id' => $tax->id,
            'cost_price' => 5,
            'sale_price' => 8,
            'opening_stock' => 0,
            'reorder_level' => 50,
            'track_stock' => true,
            'is_active' => true,
        ]);

        $summary = $metricsService->summary();
        $backlog = $metricsService->backlogQuality();

        $this->assertSame(1, $summary['today_sales_deliveries']);
        $this->assertSame(1, $summary['today_purchase_receipts']);
        $this->assertSame(1, $summary['open_approved_sales_orders']);
        $this->assertSame(1, $summary['open_approved_purchase_orders']);
        $this->assertSame(1, $summary['partially_delivered_sales_orders']);
        $this->assertSame(1, $summary['partially_received_purchase_orders']);
        $this->assertSame(1, $summary['low_stock_alert_products']);
        $this->assertSame(1, $backlog['open_approved_sales_orders']);
        $this->assertSame(1, $backlog['partially_delivered_sales_orders']);
        $this->assertSame(1, $backlog['open_approved_purchase_orders']);
        $this->assertSame(1, $backlog['partially_received_purchase_orders']);

        $this->assertSame('approved', $openSalesOrder->refresh()->status);
        $this->assertSame('approved', $openPurchaseOrder->refresh()->status);
    }

    public function test_operations_trend_returns_last_7_days_series_with_expected_counts(): void
    {
        $this->seed();

        $metricsService = app(DashboardMetricsService::class);
        $salesOrderService = app(SalesOrderService::class);
        $salesDeliveryService = app(SalesDeliveryService::class);
        $purchaseOrderService = app(PurchaseOrderService::class);
        $purchaseReceiptService = app(PurchaseReceiptService::class);

        $customer = Customer::query()->where('code', 'CUST-001')->firstOrFail();
        $supplier = Supplier::query()->where('code', 'SUP-001')->firstOrFail();
        $warehouse = Warehouse::query()->where('code', 'MAIN')->firstOrFail();
        $product = Product::query()->where('sku', 'PROD-001')->firstOrFail();

        $salesOrderToday = $salesOrderService->approve($salesOrderService->submit($salesOrderService->create([
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->toDateString(),
        ], [[
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 15,
            'tax_rate' => 0,
        ]])));

        $salesOrderTwoDaysAgo = $salesOrderService->approve($salesOrderService->submit($salesOrderService->create([
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->subDays(2)->toDateString(),
        ], [[
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 15,
            'tax_rate' => 0,
        ]])));

        $salesDeliveryService->deliver([
            'sales_order_id' => $salesOrderToday->id,
            'delivery_date' => now()->toDateString(),
        ], [[
            'sales_order_item_id' => $salesOrderToday->items()->firstOrFail()->id,
            'delivered_qty' => 1,
        ]]);

        $salesDeliveryService->deliver([
            'sales_order_id' => $salesOrderTwoDaysAgo->id,
            'delivery_date' => now()->subDays(2)->toDateString(),
        ], [[
            'sales_order_item_id' => $salesOrderTwoDaysAgo->items()->firstOrFail()->id,
            'delivered_qty' => 1,
        ]]);

        $purchaseOrderYesterday = $purchaseOrderService->approve($purchaseOrderService->submit($purchaseOrderService->create([
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->subDay()->toDateString(),
        ], [[
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_price' => 11,
            'tax_rate' => 0,
        ]])));

        $purchaseOrderToday = $purchaseOrderService->approve($purchaseOrderService->submit($purchaseOrderService->create([
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->toDateString(),
        ], [[
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_price' => 11,
            'tax_rate' => 0,
        ]])));

        $purchaseReceiptService->receive([
            'purchase_order_id' => $purchaseOrderYesterday->id,
            'received_date' => now()->subDay()->toDateString(),
        ], [[
            'purchase_order_item_id' => $purchaseOrderYesterday->items()->firstOrFail()->id,
            'received_qty' => 1,
        ]]);

        $purchaseReceiptService->receive([
            'purchase_order_id' => $purchaseOrderToday->id,
            'received_date' => now()->toDateString(),
        ], [[
            'purchase_order_item_id' => $purchaseOrderToday->items()->firstOrFail()->id,
            'received_qty' => 1,
        ]]);

        $trend = $metricsService->operationsTrend(7);

        $this->assertCount(7, $trend['labels']);
        $this->assertCount(7, $trend['sales_deliveries']);
        $this->assertCount(7, $trend['purchase_receipts']);
        $this->assertSame(1, $trend['sales_deliveries'][4]);
        $this->assertSame(1, $trend['sales_deliveries'][6]);
        $this->assertSame(1, $trend['purchase_receipts'][5]);
        $this->assertSame(1, $trend['purchase_receipts'][6]);
    }
}
