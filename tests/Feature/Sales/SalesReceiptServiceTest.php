<?php

namespace Tests\Feature\Sales;

use App\Modules\Accounting\Models\ChartOfAccount;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\Product;
use App\Modules\MasterData\Models\Warehouse;
use App\Modules\Sales\Models\SalesInvoice;
use App\Modules\Sales\Models\SalesReceipt;
use App\Modules\Sales\Services\SalesDeliveryService;
use App\Modules\Sales\Services\SalesInvoiceService;
use App\Modules\Sales\Services\SalesOrderService;
use App\Modules\Sales\Services\SalesReceiptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesReceiptServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_partial_receipt_updates_invoice_and_posts_journal_entry(): void
    {
        $this->seed();

        $invoice = $this->createPostedInvoice(quantity: 6, unitPrice: 15, taxRate: 0);
        $receipt = app(SalesReceiptService::class)->receive([
            'sales_invoice_id' => $invoice->id,
            'receipt_date' => now()->toDateString(),
            'amount' => 30,
            'payment_method' => 'cash',
        ]);

        $invoice->refresh();

        $this->assertSame('confirmed', $receipt->status);
        $this->assertNotNull($receipt->posted_at);
        $this->assertSame('partially_paid', $invoice->status);
        $this->assertSame('30.0000', $invoice->paid_total);

        $journal = JournalEntry::query()
            ->where('source_type', SalesReceipt::class)
            ->where('source_id', $receipt->id)
            ->first();

        $this->assertNotNull($journal);
        $this->assertSame('posted', $journal->status);
        $this->assertSame('30.0000', $journal->total_debit);
        $this->assertSame('30.0000', $journal->total_credit);

        $cash = ChartOfAccount::query()->where('code', '1000')->firstOrFail();
        $ar = ChartOfAccount::query()->where('code', '1100')->firstOrFail();

        $this->assertDatabaseHas('journal_lines', [
            'journal_entry_id' => $journal->id,
            'chart_of_account_id' => $cash->id,
            'debit' => 30.0000,
            'credit' => 0.0000,
        ]);
        $this->assertDatabaseHas('journal_lines', [
            'journal_entry_id' => $journal->id,
            'chart_of_account_id' => $ar->id,
            'debit' => 0.0000,
            'credit' => 30.0000,
        ]);
    }

    public function test_second_receipt_can_close_invoice(): void
    {
        $this->seed();

        $invoice = $this->createPostedInvoice(quantity: 6, unitPrice: 15, taxRate: 0);
        $service = app(SalesReceiptService::class);

        $service->receive([
            'sales_invoice_id' => $invoice->id,
            'receipt_date' => now()->toDateString(),
            'amount' => 30,
            'payment_method' => 'cash',
        ]);

        $service->receive([
            'sales_invoice_id' => $invoice->id,
            'receipt_date' => now()->toDateString(),
            'amount' => 60,
            'payment_method' => 'cash',
        ]);

        $invoice->refresh();

        $this->assertSame('paid', $invoice->status);
        $this->assertSame('90.0000', $invoice->paid_total);
        $this->assertSame(2, JournalEntry::query()->where('source_type', SalesReceipt::class)->count());
    }

    public function test_receipt_cannot_exceed_outstanding_balance(): void
    {
        $this->seed();

        $invoice = $this->createPostedInvoice(quantity: 6, unitPrice: 15, taxRate: 0);

        $this->expectException(\DomainException::class);

        app(SalesReceiptService::class)->receive([
            'sales_invoice_id' => $invoice->id,
            'receipt_date' => now()->toDateString(),
            'amount' => 120,
            'payment_method' => 'cash',
        ]);
    }

    protected function createPostedInvoice(float $quantity, float $unitPrice, float $taxRate): SalesInvoice
    {
        $orderService = app(SalesOrderService::class);
        $deliveryService = app(SalesDeliveryService::class);
        $invoiceService = app(SalesInvoiceService::class);

        $product = Product::query()->where('sku', 'PROD-001')->firstOrFail();
        $warehouse = Warehouse::query()->where('code', 'MAIN')->firstOrFail();
        $customer = Customer::query()->where('code', 'CUST-001')->firstOrFail();

        $order = $orderService->create([
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->toDateString(),
        ], [[
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'tax_rate' => $taxRate,
        ]]);

        $approved = $orderService->approve($orderService->submit($order));
        $delivery = $deliveryService->deliver([
            'sales_order_id' => $approved->id,
            'delivery_date' => now()->toDateString(),
        ], [[
            'sales_order_item_id' => $approved->items()->firstOrFail()->id,
            'delivered_qty' => $quantity,
        ]]);

        $invoice = $invoiceService->createFromDelivery([
            'sales_delivery_id' => $delivery->id,
            'invoice_date' => now()->toDateString(),
        ]);

        return $invoiceService->post($invoice);
    }
}

