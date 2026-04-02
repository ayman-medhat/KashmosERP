<?php

namespace Tests\Feature\Core;

use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\Product;
use App\Modules\MasterData\Models\Supplier;
use App\Modules\MasterData\Models\Warehouse;
use App\Modules\Purchasing\Models\PurchaseReceipt;
use App\Modules\Purchasing\Services\PurchaseOrderService;
use App\Modules\Sales\Models\SalesDelivery;
use App\Modules\Sales\Services\SalesOrderService;
use App\Modules\Sales\Services\SalesQuotationService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class WorkflowIntegrityDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_rejects_over_delivery_quantity(): void
    {
        $this->seed();

        $product = Product::query()->where('sku', 'PROD-001')->firstOrFail();
        $warehouse = Warehouse::query()->where('code', 'MAIN')->firstOrFail();
        $customer = Customer::query()->where('code', 'CUST-001')->firstOrFail();

        $salesOrder = app(SalesOrderService::class)->approve(
            app(SalesOrderService::class)->submit(
                app(SalesOrderService::class)->create([
                    'customer_id' => $customer->id,
                    'warehouse_id' => $warehouse->id,
                    'order_date' => now()->toDateString(),
                ], [[
                    'product_id' => $product->id,
                    'quantity' => 5,
                    'unit_price' => 15,
                    'tax_rate' => 0,
                ]]),
            ),
        );

        $salesDelivery = SalesDelivery::query()->create([
            'uuid' => (string) Str::uuid(),
            'delivery_no' => 'SDN-INT-00001',
            'sales_order_id' => $salesOrder->id,
            'warehouse_id' => $warehouse->id,
            'delivery_date' => now()->toDateString(),
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        $this->expectException(QueryException::class);

        DB::table('sales_delivery_items')->insert([
            'sales_delivery_id' => $salesDelivery->id,
            'sales_order_item_id' => $salesOrder->items()->firstOrFail()->id,
            'product_id' => $product->id,
            'ordered_qty' => 5,
            'delivered_qty' => 6,
            'unit_price' => 15,
            'line_total' => 90,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_database_rejects_over_receipt_quantity(): void
    {
        $this->seed();

        $product = Product::query()->where('sku', 'PROD-001')->firstOrFail();
        $warehouse = Warehouse::query()->where('code', 'MAIN')->firstOrFail();
        $supplier = Supplier::query()->where('code', 'SUP-001')->firstOrFail();

        $purchaseOrder = app(PurchaseOrderService::class)->approve(
            app(PurchaseOrderService::class)->submit(
                app(PurchaseOrderService::class)->create([
                    'supplier_id' => $supplier->id,
                    'warehouse_id' => $warehouse->id,
                    'order_date' => now()->toDateString(),
                ], [[
                    'product_id' => $product->id,
                    'quantity' => 5,
                    'unit_price' => 11,
                    'tax_rate' => 0,
                ]]),
            ),
        );

        $purchaseReceipt = PurchaseReceipt::query()->create([
            'uuid' => (string) Str::uuid(),
            'receipt_no' => 'GRN-INT-00001',
            'purchase_order_id' => $purchaseOrder->id,
            'warehouse_id' => $warehouse->id,
            'received_date' => now()->toDateString(),
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        $this->expectException(QueryException::class);

        DB::table('purchase_receipt_items')->insert([
            'purchase_receipt_id' => $purchaseReceipt->id,
            'purchase_order_item_id' => $purchaseOrder->items()->firstOrFail()->id,
            'product_id' => $product->id,
            'ordered_qty' => 5,
            'received_qty' => 6,
            'unit_cost' => 11,
            'line_total' => 66,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_database_rejects_sales_order_status_without_required_approval_timestamp(): void
    {
        $this->seed();

        $product = Product::query()->where('sku', 'PROD-001')->firstOrFail();
        $warehouse = Warehouse::query()->where('code', 'MAIN')->firstOrFail();
        $customer = Customer::query()->where('code', 'CUST-001')->firstOrFail();

        $salesOrder = app(SalesOrderService::class)->create([
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->toDateString(),
        ], [[
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 15,
            'tax_rate' => 0,
        ]]);

        $this->expectException(QueryException::class);

        DB::table('sales_orders')->where('id', $salesOrder->id)->update([
            'status' => 'approved',
            'approved_at' => null,
            'updated_at' => now(),
        ]);
    }

    public function test_database_rejects_purchase_order_status_without_required_approval_timestamp(): void
    {
        $this->seed();

        $product = Product::query()->where('sku', 'PROD-001')->firstOrFail();
        $warehouse = Warehouse::query()->where('code', 'MAIN')->firstOrFail();
        $supplier = Supplier::query()->where('code', 'SUP-001')->firstOrFail();

        $purchaseOrder = app(PurchaseOrderService::class)->create([
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->toDateString(),
        ], [[
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 11,
            'tax_rate' => 0,
        ]]);

        $this->expectException(QueryException::class);

        DB::table('purchase_orders')->where('id', $purchaseOrder->id)->update([
            'status' => 'approved',
            'approved_at' => null,
            'updated_at' => now(),
        ]);
    }

    public function test_database_rejects_converted_quotation_without_conversion_fields(): void
    {
        $this->seed();

        $product = Product::query()->where('sku', 'PROD-001')->firstOrFail();
        $warehouse = Warehouse::query()->where('code', 'MAIN')->firstOrFail();
        $customer = Customer::query()->where('code', 'CUST-001')->firstOrFail();

        $quotation = app(SalesQuotationService::class)->create([
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'quotation_date' => now()->toDateString(),
        ], [[
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 15,
            'tax_rate' => 0,
        ]]);

        $this->expectException(QueryException::class);

        DB::table('sales_quotations')->where('id', $quotation->id)->update([
            'status' => 'converted',
            'approved_at' => now(),
            'converted_at' => null,
            'converted_sales_order_id' => null,
            'updated_at' => now(),
        ]);
    }
}
