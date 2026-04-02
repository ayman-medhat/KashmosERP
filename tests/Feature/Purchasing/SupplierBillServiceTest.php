<?php

namespace Tests\Feature\Purchasing;

use App\Modules\MasterData\Models\Product;
use App\Modules\MasterData\Models\Supplier;
use App\Modules\MasterData\Models\Warehouse;
use App\Modules\Purchasing\Models\PurchaseReceipt;
use App\Modules\Purchasing\Models\SupplierBill;
use App\Modules\Purchasing\Services\PurchaseOrderService;
use App\Modules\Purchasing\Services\PurchaseReceiptService;
use App\Modules\Purchasing\Services\SupplierBillService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierBillServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_supplier_bill_from_purchase_receipt(): void
    {
        $this->seed();

        $receipt = $this->createConfirmedReceipt(quantity: 5, unitCost: 11, taxRate: 0);
        $bill = app(SupplierBillService::class)->createFromReceipt([
            'purchase_receipt_id' => $receipt->id,
            'bill_date' => now()->toDateString(),
        ]);

        $this->assertSame('draft', $bill->status);
        $this->assertSame('55.0000', $bill->subtotal);
        $this->assertSame('0.0000', $bill->tax_total);
        $this->assertSame('55.0000', $bill->grand_total);
        $this->assertCount(1, $bill->items);
    }

    public function test_it_posts_supplier_bill(): void
    {
        $this->seed();

        $receipt = $this->createConfirmedReceipt(quantity: 5, unitCost: 11, taxRate: 0);
        $service = app(SupplierBillService::class);

        $bill = $service->createFromReceipt([
            'purchase_receipt_id' => $receipt->id,
            'bill_date' => now()->toDateString(),
        ]);

        $posted = $service->post($bill);

        $this->assertSame('posted', $posted->status);
        $this->assertNotNull($posted->posted_at);
    }

    public function test_it_rejects_duplicate_supplier_bill_for_same_receipt(): void
    {
        $this->seed();

        $receipt = $this->createConfirmedReceipt(quantity: 5, unitCost: 11, taxRate: 0);
        $service = app(SupplierBillService::class);

        $service->createFromReceipt([
            'purchase_receipt_id' => $receipt->id,
            'bill_date' => now()->toDateString(),
        ]);

        $this->expectException(\DomainException::class);

        $service->createFromReceipt([
            'purchase_receipt_id' => $receipt->id,
            'bill_date' => now()->toDateString(),
        ]);
    }

    protected function createConfirmedReceipt(float $quantity, float $unitCost, float $taxRate): PurchaseReceipt
    {
        $orderService = app(PurchaseOrderService::class);
        $receiptService = app(PurchaseReceiptService::class);

        $product = Product::query()->where('sku', 'PROD-001')->firstOrFail();
        $warehouse = Warehouse::query()->where('code', 'MAIN')->firstOrFail();
        $supplier = Supplier::query()->where('code', 'SUP-001')->firstOrFail();

        $order = $orderService->create([
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->toDateString(),
        ], [[
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price' => $unitCost,
            'tax_rate' => $taxRate,
        ]]);

        $approved = $orderService->approve($orderService->submit($order));

        return $receiptService->receive([
            'purchase_order_id' => $approved->id,
            'received_date' => now()->toDateString(),
        ], [[
            'purchase_order_item_id' => $approved->items()->firstOrFail()->id,
            'received_qty' => $quantity,
        ]]);
    }
}

