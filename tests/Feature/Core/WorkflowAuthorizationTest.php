<?php

namespace Tests\Feature\Core;

use App\Core\Models\User;
use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\Product;
use App\Modules\MasterData\Models\Supplier;
use App\Modules\MasterData\Models\Warehouse;
use App\Modules\Purchasing\Models\PurchaseOrder;
use App\Modules\Purchasing\Models\PurchaseReceipt;
use App\Modules\Purchasing\Models\SupplierBill;
use App\Modules\Purchasing\Models\SupplierPayment;
use App\Modules\Purchasing\Services\PurchaseOrderService;
use App\Modules\Sales\Models\SalesDelivery;
use App\Modules\Sales\Models\SalesInvoice;
use App\Modules\Sales\Models\SalesOrder;
use App\Modules\Sales\Models\SalesQuotation;
use App\Modules\Sales\Models\SalesReceipt;
use App\Modules\Sales\Services\SalesOrderService;
use App\Modules\Sales\Services\SalesQuotationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class WorkflowAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_workflow_actions_are_denied_without_permissions(): void
    {
        $this->seed();

        $user = User::factory()->create([
            'email' => 'ops-denied@kashmos.test',
            'is_active' => true,
        ]);

        [
            'salesOrder' => $salesOrder,
            'purchaseOrder' => $purchaseOrder,
            'salesQuotation' => $salesQuotation,
        ] = $this->createWorkflowDraftRecords();

        $gate = Gate::forUser($user);

        $this->assertFalse($gate->allows('submit', $salesOrder));
        $this->assertFalse($gate->allows('approve', $salesOrder));
        $this->assertFalse($gate->allows('cancel', $salesOrder));

        $this->assertFalse($gate->allows('submit', $purchaseOrder));
        $this->assertFalse($gate->allows('approve', $purchaseOrder));
        $this->assertFalse($gate->allows('cancel', $purchaseOrder));

        $this->assertFalse($gate->allows('submit', $salesQuotation));
        $this->assertFalse($gate->allows('approve', $salesQuotation));
        $this->assertFalse($gate->allows('convert', $salesQuotation));
        $this->assertFalse($gate->allows('cancel', $salesQuotation));

        $this->assertFalse($gate->allows('create', SalesDelivery::class));
        $this->assertFalse($gate->allows('create', PurchaseReceipt::class));
        $this->assertFalse($gate->allows('create', SalesInvoice::class));
        $this->assertFalse($gate->allows('create', SalesReceipt::class));
        $this->assertFalse($gate->allows('create', SupplierBill::class));
        $this->assertFalse($gate->allows('create', SupplierPayment::class));
    }

    public function test_workflow_actions_are_allowed_with_required_permissions(): void
    {
        $this->seed();

        $user = User::factory()->create([
            'email' => 'ops-allowed@kashmos.test',
            'is_active' => true,
        ]);

        $user->givePermissionTo([
            'sales.sales-orders.submit',
            'sales.sales-orders.approve',
            'sales.sales-orders.cancel',
            'purchasing.purchase-orders.submit',
            'purchasing.purchase-orders.approve',
            'purchasing.purchase-orders.cancel',
            'sales.quotations.submit',
            'sales.quotations.approve',
            'sales.quotations.convert',
            'sales.quotations.cancel',
            'sales.sales-deliveries.create',
            'sales.sales-deliveries.confirm',
            'purchasing.purchase-receipts.create',
            'purchasing.purchase-receipts.confirm',
            'sales.sales-invoices.create',
            'sales.sales-receipts.create',
            'purchasing.supplier-bills.create',
            'purchasing.supplier-payments.create',
        ]);

        [
            'salesOrder' => $salesOrder,
            'purchaseOrder' => $purchaseOrder,
            'salesQuotation' => $salesQuotation,
        ] = $this->createWorkflowDraftRecords();

        $gate = Gate::forUser($user);

        $this->assertTrue($gate->allows('submit', $salesOrder));
        $this->assertTrue($gate->allows('approve', $salesOrder));
        $this->assertTrue($gate->allows('cancel', $salesOrder));

        $this->assertTrue($gate->allows('submit', $purchaseOrder));
        $this->assertTrue($gate->allows('approve', $purchaseOrder));
        $this->assertTrue($gate->allows('cancel', $purchaseOrder));

        $this->assertTrue($gate->allows('submit', $salesQuotation));
        $this->assertTrue($gate->allows('approve', $salesQuotation));
        $this->assertTrue($gate->allows('convert', $salesQuotation));
        $this->assertTrue($gate->allows('cancel', $salesQuotation));

        $this->assertTrue($gate->allows('create', SalesDelivery::class));
        $this->assertTrue($gate->allows('create', PurchaseReceipt::class));
        $this->assertTrue($gate->allows('create', SalesInvoice::class));
        $this->assertTrue($gate->allows('create', SalesReceipt::class));
        $this->assertTrue($gate->allows('create', SupplierBill::class));
        $this->assertTrue($gate->allows('create', SupplierPayment::class));
    }

    public function test_delivery_and_receipt_create_require_confirm_permission(): void
    {
        $this->seed();

        $user = User::factory()->create([
            'email' => 'ops-partial@kashmos.test',
            'is_active' => true,
        ]);

        $user->givePermissionTo([
            'sales.sales-deliveries.create',
            'purchasing.purchase-receipts.create',
        ]);

        $gate = Gate::forUser($user);

        $this->assertFalse($gate->allows('create', SalesDelivery::class));
        $this->assertFalse($gate->allows('create', PurchaseReceipt::class));

        $user->givePermissionTo([
            'sales.sales-deliveries.confirm',
            'purchasing.purchase-receipts.confirm',
        ]);

        $this->assertTrue($gate->allows('create', SalesDelivery::class));
        $this->assertTrue($gate->allows('create', PurchaseReceipt::class));
    }

    /**
     * @return array{
     *     salesOrder: SalesOrder,
     *     purchaseOrder: PurchaseOrder,
     *     salesQuotation: SalesQuotation
     * }
     */
    private function createWorkflowDraftRecords(): array
    {
        $product = Product::query()->where('sku', 'PROD-001')->firstOrFail();
        $warehouse = Warehouse::query()->where('code', 'MAIN')->firstOrFail();
        $customer = Customer::query()->where('code', 'CUST-001')->firstOrFail();
        $supplier = Supplier::query()->where('code', 'SUP-001')->firstOrFail();

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

        $salesQuotation = app(SalesQuotationService::class)->create([
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'quotation_date' => now()->toDateString(),
        ], [[
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 15,
            'tax_rate' => 0,
        ]]);

        return [
            'salesOrder' => $salesOrder,
            'purchaseOrder' => $purchaseOrder,
            'salesQuotation' => $salesQuotation,
        ];
    }
}
