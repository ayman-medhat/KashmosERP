<?php

namespace Tests\Feature\Purchasing;

use App\Modules\Accounting\Models\ChartOfAccount;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Inventory\Services\StockMovementService;
use App\Modules\MasterData\Models\Product;
use App\Modules\MasterData\Models\Supplier;
use App\Modules\MasterData\Models\Warehouse;
use App\Modules\Purchasing\Models\PurchaseReceipt;
use App\Modules\Purchasing\Services\PurchaseOrderService;
use App\Modules\Purchasing\Services\PurchaseReceiptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseReceiptServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_partial_receipt_updates_stock_and_order_status(): void
    {
        $this->seed();

        $orderService = app(PurchaseOrderService::class);
        $receiptService = app(PurchaseReceiptService::class);
        $stockService = app(StockMovementService::class);

        $product = Product::query()->where('sku', 'PROD-001')->firstOrFail();
        $warehouse = Warehouse::query()->where('code', 'MAIN')->firstOrFail();
        $supplier = Supplier::query()->where('code', 'SUP-001')->firstOrFail();

        $order = $orderService->create([
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->toDateString(),
        ], [
            [
                'product_id' => $product->id,
                'quantity' => 10,
                'unit_price' => 11,
                'tax_rate' => 0,
            ],
        ]);

        $approved = $orderService->approve($orderService->submit($order));

        $receipt = $receiptService->receive([
            'purchase_order_id' => $approved->id,
            'received_date' => now()->toDateString(),
        ], [
            [
                'purchase_order_item_id' => $approved->items()->firstOrFail()->id,
                'received_qty' => 4,
            ],
        ]);

        $balance = $stockService->currentStock($product->id, $warehouse->id);
        $approved->refresh()->load('items');

        $this->assertSame('confirmed', $receipt->status);
        $this->assertSame('partially_received', $approved->status);
        $this->assertNotNull($approved->posted_to_stock_at);
        $this->assertSame('4.000000', $approved->items->firstOrFail()->received_qty);
        $this->assertSame(104.0, $balance);

        $journal = JournalEntry::query()
            ->where('source_type', PurchaseReceipt::class)
            ->where('source_id', $receipt->id)
            ->first();

        $this->assertNotNull($journal);
        $this->assertSame('posted', $journal->status);
        $this->assertSame('44.0000', $journal->total_debit);
        $this->assertSame('44.0000', $journal->total_credit);
        $this->assertSame(2, $journal->lines()->count());

        $inventory = ChartOfAccount::query()->where('code', '1200')->firstOrFail();
        $accountsPayable = ChartOfAccount::query()->where('code', '2000')->firstOrFail();

        $this->assertDatabaseHas('journal_lines', [
            'journal_entry_id' => $journal->id,
            'chart_of_account_id' => $inventory->id,
            'debit' => 44.0000,
            'credit' => 0.0000,
        ]);
        $this->assertDatabaseHas('journal_lines', [
            'journal_entry_id' => $journal->id,
            'chart_of_account_id' => $accountsPayable->id,
            'debit' => 0.0000,
            'credit' => 44.0000,
        ]);
    }

    public function test_second_receipt_can_close_purchase_order(): void
    {
        $this->seed();

        $orderService = app(PurchaseOrderService::class);
        $receiptService = app(PurchaseReceiptService::class);
        $stockService = app(StockMovementService::class);

        $product = Product::query()->where('sku', 'PROD-001')->firstOrFail();
        $warehouse = Warehouse::query()->where('code', 'MAIN')->firstOrFail();
        $supplier = Supplier::query()->where('code', 'SUP-001')->firstOrFail();

        $order = $orderService->create([
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->toDateString(),
        ], [
            [
                'product_id' => $product->id,
                'quantity' => 7,
                'unit_price' => 11,
                'tax_rate' => 0,
            ],
        ]);

        $approved = $orderService->approve($orderService->submit($order));
        $lineId = $approved->items()->firstOrFail()->id;

        $receiptService->receive([
            'purchase_order_id' => $approved->id,
            'received_date' => now()->toDateString(),
        ], [
            [
                'purchase_order_item_id' => $lineId,
                'received_qty' => 2,
            ],
        ]);

        $receiptService->receive([
            'purchase_order_id' => $approved->id,
            'received_date' => now()->toDateString(),
        ], [
            [
                'purchase_order_item_id' => $lineId,
                'received_qty' => 5,
            ],
        ]);

        $approved->refresh()->load('items');
        $balance = $stockService->currentStock($product->id, $warehouse->id);

        $this->assertSame('received', $approved->status);
        $this->assertSame('7.000000', $approved->items->firstOrFail()->received_qty);
        $this->assertSame(107.0, $balance);
        $this->assertSame(2, JournalEntry::query()->where('source_type', PurchaseReceipt::class)->count());
    }

    public function test_receipt_cannot_exceed_remaining_order_quantity(): void
    {
        $this->seed();

        $orderService = app(PurchaseOrderService::class);
        $receiptService = app(PurchaseReceiptService::class);

        $product = Product::query()->where('sku', 'PROD-001')->firstOrFail();
        $warehouse = Warehouse::query()->where('code', 'MAIN')->firstOrFail();
        $supplier = Supplier::query()->where('code', 'SUP-001')->firstOrFail();

        $order = $orderService->create([
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

        $approved = $orderService->approve($orderService->submit($order));

        $this->expectException(\DomainException::class);

        $receiptService->receive([
            'purchase_order_id' => $approved->id,
            'received_date' => now()->toDateString(),
        ], [
            [
                'purchase_order_item_id' => $approved->items()->firstOrFail()->id,
                'received_qty' => 4,
            ],
        ]);
    }

    public function test_receipt_requires_approved_or_partially_received_order(): void
    {
        $this->seed();

        $orderService = app(PurchaseOrderService::class);
        $receiptService = app(PurchaseReceiptService::class);

        $product = Product::query()->where('sku', 'PROD-001')->firstOrFail();
        $warehouse = Warehouse::query()->where('code', 'MAIN')->firstOrFail();
        $supplier = Supplier::query()->where('code', 'SUP-001')->firstOrFail();

        $order = $orderService->create([
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

        $receiptService->receive([
            'purchase_order_id' => $order->id,
            'received_date' => now()->toDateString(),
        ], [
            [
                'purchase_order_item_id' => $order->items()->firstOrFail()->id,
                'received_qty' => 1,
            ],
        ]);
    }

    public function test_receipt_rejects_order_item_that_belongs_to_another_order(): void
    {
        $this->seed();

        $orderService = app(PurchaseOrderService::class);
        $receiptService = app(PurchaseReceiptService::class);

        $product = Product::query()->where('sku', 'PROD-001')->firstOrFail();
        $warehouse = Warehouse::query()->where('code', 'MAIN')->firstOrFail();
        $supplier = Supplier::query()->where('code', 'SUP-001')->firstOrFail();

        $orderA = $orderService->approve($orderService->submit($orderService->create([
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->toDateString(),
        ], [[
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_price' => 11,
            'tax_rate' => 0,
        ]])));

        $orderB = $orderService->approve($orderService->submit($orderService->create([
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->toDateString(),
        ], [[
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 11,
            'tax_rate' => 0,
        ]])));

        $this->expectException(\DomainException::class);

        $receiptService->receive([
            'purchase_order_id' => $orderA->id,
            'received_date' => now()->toDateString(),
        ], [
            [
                'purchase_order_item_id' => $orderB->items()->firstOrFail()->id,
                'received_qty' => 1,
            ],
        ]);
    }
}
