<?php

namespace Tests\Feature\Purchasing;

use App\Modules\Accounting\Models\ChartOfAccount;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\MasterData\Models\Product;
use App\Modules\MasterData\Models\Supplier;
use App\Modules\MasterData\Models\Warehouse;
use App\Modules\Purchasing\Models\PurchaseReceipt;
use App\Modules\Purchasing\Models\SupplierBill;
use App\Modules\Purchasing\Models\SupplierPayment;
use App\Modules\Purchasing\Services\PurchaseOrderService;
use App\Modules\Purchasing\Services\PurchaseReceiptService;
use App\Modules\Purchasing\Services\SupplierBillService;
use App\Modules\Purchasing\Services\SupplierPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierPaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_partial_payment_updates_bill_and_posts_journal_entry(): void
    {
        $this->seed();

        $bill = $this->createPostedSupplierBill(quantity: 5, unitCost: 11, taxRate: 0);
        $payment = app(SupplierPaymentService::class)->pay([
            'supplier_bill_id' => $bill->id,
            'payment_date' => now()->toDateString(),
            'amount' => 20,
            'payment_method' => 'bank_transfer',
        ]);

        $bill->refresh();

        $this->assertSame('confirmed', $payment->status);
        $this->assertNotNull($payment->posted_at);
        $this->assertSame('partially_paid', $bill->status);
        $this->assertSame('20.0000', $bill->paid_total);

        $journal = JournalEntry::query()
            ->where('source_type', SupplierPayment::class)
            ->where('source_id', $payment->id)
            ->first();

        $this->assertNotNull($journal);
        $this->assertSame('posted', $journal->status);
        $this->assertSame('20.0000', $journal->total_debit);
        $this->assertSame('20.0000', $journal->total_credit);

        $ap = ChartOfAccount::query()->where('code', '2000')->firstOrFail();
        $cash = ChartOfAccount::query()->where('code', '1000')->firstOrFail();

        $this->assertDatabaseHas('journal_lines', [
            'journal_entry_id' => $journal->id,
            'chart_of_account_id' => $ap->id,
            'debit' => 20.0000,
            'credit' => 0.0000,
        ]);
        $this->assertDatabaseHas('journal_lines', [
            'journal_entry_id' => $journal->id,
            'chart_of_account_id' => $cash->id,
            'debit' => 0.0000,
            'credit' => 20.0000,
        ]);
    }

    public function test_second_payment_can_close_supplier_bill(): void
    {
        $this->seed();

        $bill = $this->createPostedSupplierBill(quantity: 5, unitCost: 11, taxRate: 0);
        $service = app(SupplierPaymentService::class);

        $service->pay([
            'supplier_bill_id' => $bill->id,
            'payment_date' => now()->toDateString(),
            'amount' => 20,
        ]);

        $service->pay([
            'supplier_bill_id' => $bill->id,
            'payment_date' => now()->toDateString(),
            'amount' => 35,
        ]);

        $bill->refresh();

        $this->assertSame('paid', $bill->status);
        $this->assertSame('55.0000', $bill->paid_total);
        $this->assertSame(2, JournalEntry::query()->where('source_type', SupplierPayment::class)->count());
    }

    public function test_payment_cannot_exceed_supplier_bill_outstanding_balance(): void
    {
        $this->seed();

        $bill = $this->createPostedSupplierBill(quantity: 5, unitCost: 11, taxRate: 0);

        $this->expectException(\DomainException::class);

        app(SupplierPaymentService::class)->pay([
            'supplier_bill_id' => $bill->id,
            'payment_date' => now()->toDateString(),
            'amount' => 80,
        ]);
    }

    protected function createPostedSupplierBill(float $quantity, float $unitCost, float $taxRate): SupplierBill
    {
        $orderService = app(PurchaseOrderService::class);
        $receiptService = app(PurchaseReceiptService::class);
        $billService = app(SupplierBillService::class);

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
        $receipt = $receiptService->receive([
            'purchase_order_id' => $approved->id,
            'received_date' => now()->toDateString(),
        ], [[
            'purchase_order_item_id' => $approved->items()->firstOrFail()->id,
            'received_qty' => $quantity,
        ]]);

        $bill = $billService->createFromReceipt([
            'purchase_receipt_id' => $receipt->id,
            'bill_date' => now()->toDateString(),
        ]);

        return $billService->post($bill);
    }
}

