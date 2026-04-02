<?php

namespace Tests\Feature\Accounting;

use App\Modules\Accounting\Models\ChartOfAccount;
use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\Product;
use App\Modules\MasterData\Models\Supplier;
use App\Modules\MasterData\Models\Warehouse;
use App\Modules\Purchasing\Models\SupplierBill;
use App\Modules\Purchasing\Services\PurchaseOrderService;
use App\Modules\Purchasing\Services\PurchaseReceiptService;
use App\Modules\Purchasing\Services\SupplierBillService;
use App\Modules\Sales\Models\SalesDelivery;
use App\Modules\Sales\Services\SalesDeliveryService;
use App\Modules\Sales\Services\SalesOrderService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AccountingIntegrityDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_rejects_journal_lines_with_both_debit_and_credit(): void
    {
        $this->seed();

        $cash = ChartOfAccount::query()->where('code', '1000')->firstOrFail();

        $entryId = DB::table('journal_entries')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'entry_no' => 'JE-INT-00001',
            'entry_date' => now()->toDateString(),
            'status' => 'draft',
            'source_type' => null,
            'source_id' => null,
            'reference_no' => null,
            'description_translations' => null,
            'total_debit' => 0,
            'total_credit' => 0,
            'posted_at' => null,
            'created_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(QueryException::class);

        DB::table('journal_lines')->insert([
            'journal_entry_id' => $entryId,
            'chart_of_account_id' => $cash->id,
            'line_no' => 1,
            'description_translations' => null,
            'debit' => 100,
            'credit' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_database_rejects_posted_journal_entry_without_posted_timestamp(): void
    {
        $this->seed();

        $this->expectException(QueryException::class);

        DB::table('journal_entries')->insert([
            'uuid' => (string) Str::uuid(),
            'entry_no' => 'JE-INT-00002',
            'entry_date' => now()->toDateString(),
            'status' => 'posted',
            'source_type' => null,
            'source_id' => null,
            'reference_no' => null,
            'description_translations' => null,
            'total_debit' => 100,
            'total_credit' => 100,
            'posted_at' => null,
            'created_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_database_rejects_invalid_chart_of_account_type(): void
    {
        $this->seed();

        $this->expectException(QueryException::class);

        DB::table('chart_of_accounts')->insert([
            'uuid' => (string) Str::uuid(),
            'code' => '9999',
            'name_translations' => json_encode(['en' => 'Invalid Account', 'ar' => 'حساب غير صالح'], JSON_THROW_ON_ERROR),
            'account_type' => 'invalid',
            'normal_balance' => 'debit',
            'parent_account_id' => null,
            'is_active' => true,
            'is_system' => false,
            'created_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_database_rejects_duplicate_source_journal_entries(): void
    {
        $this->seed();

        DB::table('journal_entries')->insert([
            'uuid' => (string) Str::uuid(),
            'entry_no' => 'JE-INT-00003',
            'entry_date' => now()->toDateString(),
            'status' => 'draft',
            'source_type' => 'source.document',
            'source_id' => 99,
            'reference_no' => null,
            'description_translations' => null,
            'total_debit' => 0,
            'total_credit' => 0,
            'posted_at' => null,
            'created_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(QueryException::class);

        DB::table('journal_entries')->insert([
            'uuid' => (string) Str::uuid(),
            'entry_no' => 'JE-INT-00004',
            'entry_date' => now()->toDateString(),
            'status' => 'draft',
            'source_type' => 'source.document',
            'source_id' => 99,
            'reference_no' => null,
            'description_translations' => null,
            'total_debit' => 0,
            'total_credit' => 0,
            'posted_at' => null,
            'created_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_database_rejects_sales_invoice_paid_total_above_grand_total(): void
    {
        $this->seed();

        $delivery = $this->createConfirmedSalesDelivery();

        $this->expectException(QueryException::class);

        DB::table('sales_invoices')->insert([
            'uuid' => (string) Str::uuid(),
            'invoice_no' => 'SI-INT-00001',
            'sales_delivery_id' => $delivery->id,
            'sales_order_id' => $delivery->sales_order_id,
            'customer_id' => $delivery->order->customer_id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->toDateString(),
            'status' => 'paid',
            'subtotal' => 100,
            'tax_total' => 0,
            'grand_total' => 100,
            'paid_total' => 120,
            'notes_translations' => null,
            'posted_at' => now(),
            'created_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }

    public function test_database_rejects_supplier_payment_without_posted_timestamp(): void
    {
        $this->seed();

        $bill = $this->createPostedSupplierBill();

        $this->expectException(QueryException::class);

        DB::table('supplier_payments')->insert([
            'uuid' => (string) Str::uuid(),
            'payment_no' => 'SP-INT-00001',
            'supplier_bill_id' => $bill->id,
            'supplier_id' => $bill->supplier_id,
            'payment_date' => now()->toDateString(),
            'status' => 'confirmed',
            'amount' => 10,
            'payment_method' => 'cash',
            'reference_no' => null,
            'notes_translations' => null,
            'confirmed_at' => now(),
            'posted_at' => null,
            'created_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function createConfirmedSalesDelivery(): SalesDelivery
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
            'quantity' => 2,
            'unit_price' => 15,
            'tax_rate' => 0,
        ]]);

        $approved = $orderService->approve($orderService->submit($order));

        return $deliveryService->deliver([
            'sales_order_id' => $approved->id,
            'delivery_date' => now()->toDateString(),
        ], [[
            'sales_order_item_id' => $approved->items()->firstOrFail()->id,
            'delivered_qty' => 2,
        ]])->load('order');
    }

    protected function createPostedSupplierBill(): SupplierBill
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
            'quantity' => 2,
            'unit_price' => 11,
            'tax_rate' => 0,
        ]]);

        $approved = $orderService->approve($orderService->submit($order));
        $receipt = $receiptService->receive([
            'purchase_order_id' => $approved->id,
            'received_date' => now()->toDateString(),
        ], [[
            'purchase_order_item_id' => $approved->items()->firstOrFail()->id,
            'received_qty' => 2,
        ]]);

        $bill = $billService->createFromReceipt([
            'purchase_receipt_id' => $receipt->id,
            'bill_date' => now()->toDateString(),
        ]);

        return $billService->post($bill);
    }
}
