<?php

namespace Tests\Feature\Sales;

use App\Modules\Accounting\Models\ChartOfAccount;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Inventory\Services\StockMovementService;
use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\Product;
use App\Modules\MasterData\Models\Warehouse;
use App\Modules\Sales\Models\SalesDelivery;
use App\Modules\Sales\Services\SalesDeliveryService;
use App\Modules\Sales\Services\SalesOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesDeliveryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_partial_delivery_updates_line_and_order_status(): void
    {
        $this->seed();

        $orderService = app(SalesOrderService::class);
        $deliveryService = app(SalesDeliveryService::class);
        $stockService = app(StockMovementService::class);

        $product = Product::query()->where('sku', 'PROD-001')->firstOrFail();
        $warehouse = Warehouse::query()->where('code', 'MAIN')->firstOrFail();
        $customer = Customer::query()->where('code', 'CUST-001')->firstOrFail();

        $order = $orderService->create([
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->toDateString(),
        ], [[
            'product_id' => $product->id,
            'quantity' => 10,
            'unit_price' => 15,
            'tax_rate' => 0,
        ]]);

        $approved = $orderService->approve($orderService->submit($order));

        $delivery = $deliveryService->deliver([
            'sales_order_id' => $approved->id,
            'delivery_date' => now()->toDateString(),
        ], [[
            'sales_order_item_id' => $approved->items()->firstOrFail()->id,
            'delivered_qty' => 4,
        ]]);

        $approved->refresh()->load('items');
        $remaining = $stockService->currentStock($product->id, $warehouse->id);

        $this->assertSame('confirmed', $delivery->status);
        $this->assertSame('partially_delivered', $approved->status);
        $this->assertSame('4.000000', $approved->items->firstOrFail()->delivered_qty);
        $this->assertNotNull($approved->posted_to_stock_at);
        $this->assertSame(96.0, $remaining);

        $journal = JournalEntry::query()
            ->where('source_type', SalesDelivery::class)
            ->where('source_id', $delivery->id)
            ->first();

        $this->assertNotNull($journal);
        $this->assertSame('posted', $journal->status);
        $this->assertSame('40.0000', $journal->total_debit);
        $this->assertSame('40.0000', $journal->total_credit);
        $this->assertSame(2, $journal->lines()->count());

        $inventory = ChartOfAccount::query()->where('code', '1200')->firstOrFail();
        $cogs = ChartOfAccount::query()->where('code', '5000')->firstOrFail();

        $this->assertDatabaseHas('journal_lines', [
            'journal_entry_id' => $journal->id,
            'chart_of_account_id' => $cogs->id,
            'debit' => 40.0000,
            'credit' => 0.0000,
        ]);
        $this->assertDatabaseHas('journal_lines', [
            'journal_entry_id' => $journal->id,
            'chart_of_account_id' => $inventory->id,
            'debit' => 0.0000,
            'credit' => 40.0000,
        ]);
    }

    public function test_second_delivery_can_close_sales_order(): void
    {
        $this->seed();

        $orderService = app(SalesOrderService::class);
        $deliveryService = app(SalesDeliveryService::class);
        $stockService = app(StockMovementService::class);

        $product = Product::query()->where('sku', 'PROD-001')->firstOrFail();
        $warehouse = Warehouse::query()->where('code', 'MAIN')->firstOrFail();
        $customer = Customer::query()->where('code', 'CUST-001')->firstOrFail();

        $order = $orderService->create([
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->toDateString(),
        ], [[
            'product_id' => $product->id,
            'quantity' => 7,
            'unit_price' => 15,
            'tax_rate' => 0,
        ]]);

        $approved = $orderService->approve($orderService->submit($order));
        $lineId = $approved->items()->firstOrFail()->id;

        $deliveryService->deliver([
            'sales_order_id' => $approved->id,
            'delivery_date' => now()->toDateString(),
        ], [[
            'sales_order_item_id' => $lineId,
            'delivered_qty' => 2,
        ]]);

        $deliveryService->deliver([
            'sales_order_id' => $approved->id,
            'delivery_date' => now()->toDateString(),
        ], [[
            'sales_order_item_id' => $lineId,
            'delivered_qty' => 5,
        ]]);

        $approved->refresh()->load('items');
        $remaining = $stockService->currentStock($product->id, $warehouse->id);

        $this->assertSame('delivered', $approved->status);
        $this->assertSame('7.000000', $approved->items->firstOrFail()->delivered_qty);
        $this->assertSame(93.0, $remaining);
        $this->assertSame(2, JournalEntry::query()->where('source_type', SalesDelivery::class)->count());
    }

    public function test_delivery_cannot_exceed_remaining_sales_order_quantity(): void
    {
        $this->seed();

        $orderService = app(SalesOrderService::class);
        $deliveryService = app(SalesDeliveryService::class);

        $product = Product::query()->where('sku', 'PROD-001')->firstOrFail();
        $warehouse = Warehouse::query()->where('code', 'MAIN')->firstOrFail();
        $customer = Customer::query()->where('code', 'CUST-001')->firstOrFail();

        $order = $orderService->create([
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->toDateString(),
        ], [[
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_price' => 15,
            'tax_rate' => 0,
        ]]);

        $approved = $orderService->approve($orderService->submit($order));

        $this->expectException(\DomainException::class);

        $deliveryService->deliver([
            'sales_order_id' => $approved->id,
            'delivery_date' => now()->toDateString(),
        ], [[
            'sales_order_item_id' => $approved->items()->firstOrFail()->id,
            'delivered_qty' => 4,
        ]]);
    }

    public function test_delivery_requires_approved_or_partially_delivered_order(): void
    {
        $this->seed();

        $orderService = app(SalesOrderService::class);
        $deliveryService = app(SalesDeliveryService::class);

        $product = Product::query()->where('sku', 'PROD-001')->firstOrFail();
        $warehouse = Warehouse::query()->where('code', 'MAIN')->firstOrFail();
        $customer = Customer::query()->where('code', 'CUST-001')->firstOrFail();

        $order = $orderService->create([
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->toDateString(),
        ], [[
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_price' => 15,
            'tax_rate' => 0,
        ]]);

        $this->expectException(\DomainException::class);

        $deliveryService->deliver([
            'sales_order_id' => $order->id,
            'delivery_date' => now()->toDateString(),
        ], [[
            'sales_order_item_id' => $order->items()->firstOrFail()->id,
            'delivered_qty' => 1,
        ]]);
    }

    public function test_delivery_rejects_order_item_that_belongs_to_another_order(): void
    {
        $this->seed();

        $orderService = app(SalesOrderService::class);
        $deliveryService = app(SalesDeliveryService::class);

        $product = Product::query()->where('sku', 'PROD-001')->firstOrFail();
        $warehouse = Warehouse::query()->where('code', 'MAIN')->firstOrFail();
        $customer = Customer::query()->where('code', 'CUST-001')->firstOrFail();

        $orderA = $orderService->approve($orderService->submit($orderService->create([
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->toDateString(),
        ], [[
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_price' => 15,
            'tax_rate' => 0,
        ]])));

        $orderB = $orderService->approve($orderService->submit($orderService->create([
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->toDateString(),
        ], [[
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 15,
            'tax_rate' => 0,
        ]])));

        $this->expectException(\DomainException::class);

        $deliveryService->deliver([
            'sales_order_id' => $orderA->id,
            'delivery_date' => now()->toDateString(),
        ], [[
            'sales_order_item_id' => $orderB->items()->firstOrFail()->id,
            'delivered_qty' => 1,
        ]]);
    }
}
