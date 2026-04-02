<?php

namespace Tests\Feature\Inventory;

use App\Modules\Inventory\Models\StockMovement;
use App\Modules\MasterData\Models\Product;
use App\Modules\MasterData\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockMovementSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_opening_stock_movement_is_seeded(): void
    {
        $this->seed();

        $product = Product::query()->where('sku', 'PROD-001')->first();
        $warehouse = Warehouse::query()->where('code', 'MAIN')->first();
        $movement = StockMovement::query()
            ->where('product_id', $product?->id)
            ->where('warehouse_id', $warehouse?->id)
            ->where('movement_type', 'opening')
            ->first();

        $this->assertNotNull($movement);
        $this->assertSame('100.000000', $movement->quantity);
        $this->assertSame('100.000000', $movement->balance_after);
    }
}
