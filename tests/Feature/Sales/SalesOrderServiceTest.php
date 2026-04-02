<?php

namespace Tests\Feature\Sales;

use App\Modules\Inventory\Services\StockMovementService;
use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\Product;
use App\Modules\MasterData\Models\Warehouse;
use App\Modules\Sales\Services\SalesOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesOrderServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_order_approval_does_not_post_stock_until_delivery(): void
    {
        $this->seed();

        $service = app(SalesOrderService::class);
        $stockService = app(StockMovementService::class);
        $product = Product::query()->where('sku', 'PROD-001')->firstOrFail();
        $warehouse = Warehouse::query()->where('code', 'MAIN')->firstOrFail();
        $customer = Customer::query()->where('code', 'CUST-001')->firstOrFail();

        $order = $service->create([
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->toDateString(),
        ], [
            [
                'product_id' => $product->id,
                'quantity' => 10,
                'unit_price' => 15,
                'tax_rate' => 0,
            ],
        ]);

        $order = $service->submit($order);
        $approved = $service->approve($order);
        $remaining = $stockService->currentStock($product->id, $warehouse->id);

        $this->assertSame('approved', $approved->status);
        $this->assertNull($approved->posted_to_stock_at);
        $this->assertSame(100.0, $remaining);
    }

    public function test_sales_order_approval_is_idempotent(): void
    {
        $this->seed();

        $service = app(SalesOrderService::class);
        $stockService = app(StockMovementService::class);
        $product = Product::query()->where('sku', 'PROD-001')->firstOrFail();
        $warehouse = Warehouse::query()->where('code', 'MAIN')->firstOrFail();
        $customer = Customer::query()->where('code', 'CUST-001')->firstOrFail();

        $order = $service->create([
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->toDateString(),
        ], [
            [
                'product_id' => $product->id,
                'quantity' => 5,
                'unit_price' => 15,
                'tax_rate' => 0,
            ],
        ]);

        $service->submit($order);
        $service->approve($order);
        $service->approve($order->refresh());

        $remaining = $stockService->currentStock($product->id, $warehouse->id);
        $this->assertSame(100.0, $remaining);
    }

    public function test_sales_order_must_be_submitted_before_approval(): void
    {
        $this->seed();

        $service = app(SalesOrderService::class);
        $product = Product::query()->where('sku', 'PROD-001')->firstOrFail();
        $warehouse = Warehouse::query()->where('code', 'MAIN')->firstOrFail();
        $customer = Customer::query()->where('code', 'CUST-001')->firstOrFail();

        $order = $service->create([
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->toDateString(),
        ], [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 15,
                'tax_rate' => 0,
            ],
        ]);

        $this->expectException(\DomainException::class);
        $service->approve($order);
    }

    public function test_sales_order_can_be_cancelled_from_submitted_state(): void
    {
        $this->seed();

        $service = app(SalesOrderService::class);
        $product = Product::query()->where('sku', 'PROD-001')->firstOrFail();
        $warehouse = Warehouse::query()->where('code', 'MAIN')->firstOrFail();
        $customer = Customer::query()->where('code', 'CUST-001')->firstOrFail();

        $order = $service->create([
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->toDateString(),
        ], [
            [
                'product_id' => $product->id,
                'quantity' => 3,
                'unit_price' => 15,
                'tax_rate' => 0,
            ],
        ]);

        $submitted = $service->submit($order);
        $cancelled = $service->cancel($submitted);

        $this->assertSame('cancelled', $cancelled->status);
        $this->assertNull($cancelled->posted_to_stock_at);
    }
}
