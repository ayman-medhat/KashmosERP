<?php

namespace Tests\Feature\Inventory;

use App\Core\Services\SettingsService;
use App\Modules\Inventory\Services\StockMovementService;
use App\Modules\MasterData\Models\Product;
use App\Modules\MasterData\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockMovementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_updates_running_stock_balance(): void
    {
        $this->seed();

        $service = app(StockMovementService::class);
        $product = Product::query()->where('sku', 'PROD-001')->firstOrFail();
        $warehouse = Warehouse::query()->where('code', 'MAIN')->firstOrFail();

        $movement = $service->record([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'movement_type' => 'adjustment',
            'reference_no' => 'ADJ-001',
            'quantity' => -20,
        ]);

        $this->assertSame('80.000000', $movement->balance_after);
    }

    public function test_it_blocks_negative_stock_when_disabled(): void
    {
        $this->seed();

        app(SettingsService::class)->put('inventory', 'allow_negative_stock', false);

        $service = app(StockMovementService::class);
        $product = Product::query()->where('sku', 'PROD-001')->firstOrFail();
        $warehouse = Warehouse::query()->where('code', 'MAIN')->firstOrFail();

        $this->expectException(\DomainException::class);

        $service->record([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'movement_type' => 'adjustment',
            'reference_no' => 'ADJ-NEG',
            'quantity' => -120,
        ]);
    }

    public function test_it_rejects_zero_quantity_movements(): void
    {
        $this->seed();

        $service = app(StockMovementService::class);
        $product = Product::query()->where('sku', 'PROD-001')->firstOrFail();
        $warehouse = Warehouse::query()->where('code', 'MAIN')->firstOrFail();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Stock movement quantity cannot be zero.');

        $service->record([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'movement_type' => 'adjustment',
            'reference_no' => 'ADJ-ZERO',
            'quantity' => 0,
        ]);
    }

    public function test_it_rejects_negative_unit_cost_movements(): void
    {
        $this->seed();

        $service = app(StockMovementService::class);
        $product = Product::query()->where('sku', 'PROD-001')->firstOrFail();
        $warehouse = Warehouse::query()->where('code', 'MAIN')->firstOrFail();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Unit cost cannot be negative.');

        $service->record([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'movement_type' => 'adjustment',
            'reference_no' => 'ADJ-NEG-COST',
            'quantity' => 5,
            'unit_cost' => -1,
        ]);
    }
}
