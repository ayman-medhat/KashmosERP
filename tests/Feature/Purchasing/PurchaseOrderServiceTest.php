<?php

namespace Tests\Feature\Purchasing;

use App\Modules\Inventory\Services\StockMovementService;
use App\Modules\MasterData\Models\Product;
use App\Modules\MasterData\Models\Supplier;
use App\Modules\MasterData\Models\Warehouse;
use App\Modules\Purchasing\Services\PurchaseOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_order_approval_does_not_post_stock_until_receipt(): void
    {
        $this->seed();

        $service = app(PurchaseOrderService::class);
        $stockService = app(StockMovementService::class);
        $product = Product::query()->where('sku', 'PROD-001')->firstOrFail();
        $warehouse = Warehouse::query()->where('code', 'MAIN')->firstOrFail();
        $supplier = Supplier::query()->where('code', 'SUP-001')->firstOrFail();

        $order = $service->create([
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->toDateString(),
        ], [
            [
                'product_id' => $product->id,
                'quantity' => 25,
                'unit_price' => 11,
                'tax_rate' => 0,
            ],
        ]);

        $order = $service->submit($order);
        $approved = $service->approve($order);
        $balance = $stockService->currentStock($product->id, $warehouse->id);

        $this->assertSame('approved', $approved->status);
        $this->assertNull($approved->posted_to_stock_at);
        $this->assertSame(100.0, $balance);
    }

    public function test_purchase_order_must_be_submitted_before_approval(): void
    {
        $this->seed();

        $service = app(PurchaseOrderService::class);
        $product = Product::query()->where('sku', 'PROD-001')->firstOrFail();
        $warehouse = Warehouse::query()->where('code', 'MAIN')->firstOrFail();
        $supplier = Supplier::query()->where('code', 'SUP-001')->firstOrFail();

        $order = $service->create([
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->toDateString(),
        ], [
            [
                'product_id' => $product->id,
                'quantity' => 3,
                'unit_price' => 11,
                'tax_rate' => 0,
            ],
        ]);

        $this->expectException(\DomainException::class);
        $service->approve($order);
    }

    public function test_purchase_order_can_be_cancelled_from_submitted_state(): void
    {
        $this->seed();

        $service = app(PurchaseOrderService::class);
        $product = Product::query()->where('sku', 'PROD-001')->firstOrFail();
        $warehouse = Warehouse::query()->where('code', 'MAIN')->firstOrFail();
        $supplier = Supplier::query()->where('code', 'SUP-001')->firstOrFail();

        $order = $service->create([
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->toDateString(),
        ], [
            [
                'product_id' => $product->id,
                'quantity' => 5,
                'unit_price' => 11,
                'tax_rate' => 0,
            ],
        ]);

        $submitted = $service->submit($order);
        $cancelled = $service->cancel($submitted);

        $this->assertSame('cancelled', $cancelled->status);
        $this->assertNull($cancelled->posted_to_stock_at);
    }
}
