<?php

namespace Tests\Feature\Sales;

use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\Product;
use App\Modules\MasterData\Models\Warehouse;
use App\Modules\Sales\Models\SalesQuotation;
use App\Modules\Sales\Services\SalesQuotationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesQuotationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_quotation_converts_to_sales_order_with_totals_parity(): void
    {
        $this->seed();

        $service = app(SalesQuotationService::class);
        $product = Product::query()->where('sku', 'PROD-001')->firstOrFail();
        $warehouse = Warehouse::query()->where('code', 'MAIN')->firstOrFail();
        $customer = Customer::query()->where('code', 'CUST-001')->firstOrFail();

        $quotation = $service->create([
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'quotation_date' => now()->toDateString(),
        ], [
            [
                'product_id' => $product->id,
                'quantity' => 10,
                'unit_price' => 15,
                'tax_rate' => 5,
            ],
        ]);

        $quotation = $service->submit($quotation);
        $quotation = $service->approve($quotation);
        $order = $service->convertToSalesOrder($quotation);

        $quotation = $quotation->refresh();

        $this->assertSame('converted', $quotation->status);
        $this->assertNotNull($quotation->converted_at);
        $this->assertSame($order->id, $quotation->converted_sales_order_id);
        $this->assertSame((float) $quotation->grand_total, (float) $order->grand_total);
    }

    public function test_quotation_must_be_approved_before_conversion(): void
    {
        $this->seed();

        $service = app(SalesQuotationService::class);
        $product = Product::query()->where('sku', 'PROD-001')->firstOrFail();
        $warehouse = Warehouse::query()->where('code', 'MAIN')->firstOrFail();
        $customer = Customer::query()->where('code', 'CUST-001')->firstOrFail();

        $quotation = $service->create([
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'quotation_date' => now()->toDateString(),
        ], [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 10,
                'tax_rate' => 0,
            ],
        ]);

        $this->expectException(\DomainException::class);
        $service->convertToSalesOrder($quotation);
    }

    public function test_quotation_conversion_is_idempotent(): void
    {
        $this->seed();

        $service = app(SalesQuotationService::class);
        $product = Product::query()->where('sku', 'PROD-001')->firstOrFail();
        $warehouse = Warehouse::query()->where('code', 'MAIN')->firstOrFail();
        $customer = Customer::query()->where('code', 'CUST-001')->firstOrFail();

        $quotation = $service->create([
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'quotation_date' => now()->toDateString(),
        ], [
            [
                'product_id' => $product->id,
                'quantity' => 2,
                'unit_price' => 20,
                'tax_rate' => 0,
            ],
        ]);

        $service->submit($quotation);
        $service->approve($quotation->refresh());

        $firstOrder = $service->convertToSalesOrder($quotation->refresh());
        $secondOrder = $service->convertToSalesOrder($quotation->refresh());

        $this->assertSame($firstOrder->id, $secondOrder->id);
        $this->assertSame(1, SalesQuotation::query()->where('status', 'converted')->count());
    }
}
