<?php

namespace Tests\Feature\Sales;

use App\Modules\Accounting\Models\ChartOfAccount;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\Product;
use App\Modules\MasterData\Models\Warehouse;
use App\Modules\Sales\Models\SalesDelivery;
use App\Modules\Sales\Models\SalesInvoice;
use App\Modules\Sales\Services\SalesDeliveryService;
use App\Modules\Sales\Services\SalesInvoiceService;
use App\Modules\Sales\Services\SalesOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesInvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_sales_invoice_from_delivery(): void
    {
        $this->seed();

        $delivery = $this->createConfirmedDelivery(quantity: 4, unitPrice: 15, taxRate: 0);

        $invoice = app(SalesInvoiceService::class)->createFromDelivery([
            'sales_delivery_id' => $delivery->id,
            'invoice_date' => now()->toDateString(),
        ]);

        $this->assertSame('draft', $invoice->status);
        $this->assertSame('60.0000', $invoice->subtotal);
        $this->assertSame('0.0000', $invoice->tax_total);
        $this->assertSame('60.0000', $invoice->grand_total);
        $this->assertCount(1, $invoice->items);
    }

    public function test_it_posts_sales_invoice_and_creates_journal_entry(): void
    {
        $this->seed();

        $delivery = $this->createConfirmedDelivery(quantity: 4, unitPrice: 15, taxRate: 0);
        $service = app(SalesInvoiceService::class);

        $invoice = $service->createFromDelivery([
            'sales_delivery_id' => $delivery->id,
            'invoice_date' => now()->toDateString(),
        ]);

        $posted = $service->post($invoice);

        $this->assertSame('posted', $posted->status);
        $this->assertNotNull($posted->posted_at);

        $journal = JournalEntry::query()
            ->where('source_type', SalesInvoice::class)
            ->where('source_id', $posted->id)
            ->first();

        $this->assertNotNull($journal);
        $this->assertSame('posted', $journal->status);
        $this->assertSame('60.0000', $journal->total_debit);
        $this->assertSame('60.0000', $journal->total_credit);

        $ar = ChartOfAccount::query()->where('code', '1100')->firstOrFail();
        $revenue = ChartOfAccount::query()->where('code', '4000')->firstOrFail();

        $this->assertDatabaseHas('journal_lines', [
            'journal_entry_id' => $journal->id,
            'chart_of_account_id' => $ar->id,
            'debit' => 60.0000,
            'credit' => 0.0000,
        ]);
        $this->assertDatabaseHas('journal_lines', [
            'journal_entry_id' => $journal->id,
            'chart_of_account_id' => $revenue->id,
            'debit' => 0.0000,
            'credit' => 60.0000,
        ]);
    }

    public function test_it_rejects_duplicate_invoice_for_same_delivery(): void
    {
        $this->seed();

        $delivery = $this->createConfirmedDelivery(quantity: 4, unitPrice: 15, taxRate: 0);
        $service = app(SalesInvoiceService::class);

        $service->createFromDelivery([
            'sales_delivery_id' => $delivery->id,
            'invoice_date' => now()->toDateString(),
        ]);

        $this->expectException(\DomainException::class);

        $service->createFromDelivery([
            'sales_delivery_id' => $delivery->id,
            'invoice_date' => now()->toDateString(),
        ]);
    }

    protected function createConfirmedDelivery(float $quantity, float $unitPrice, float $taxRate): SalesDelivery
    {
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
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'tax_rate' => $taxRate,
        ]]);

        $approved = $orderService->approve($orderService->submit($order));

        return $deliveryService->deliver([
            'sales_order_id' => $approved->id,
            'delivery_date' => now()->toDateString(),
        ], [[
            'sales_order_item_id' => $approved->items()->firstOrFail()->id,
            'delivered_qty' => $quantity,
        ]]);
    }
}

