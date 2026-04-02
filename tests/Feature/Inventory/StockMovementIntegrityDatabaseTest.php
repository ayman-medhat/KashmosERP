<?php

namespace Tests\Feature\Inventory;

use App\Modules\MasterData\Models\Product;
use App\Modules\MasterData\Models\Warehouse;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class StockMovementIntegrityDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_rejects_zero_quantity_stock_movements(): void
    {
        $this->seed();

        $product = Product::query()->where('sku', 'PROD-001')->firstOrFail();
        $warehouse = Warehouse::query()->where('code', 'MAIN')->firstOrFail();

        $this->expectException(QueryException::class);

        DB::table('stock_movements')->insert([
            'uuid' => (string) Str::uuid(),
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'movement_type' => 'adjustment',
            'source_type' => null,
            'source_id' => null,
            'reference_no' => 'INT-SM-ZERO',
            'quantity' => 0,
            'balance_after' => 100,
            'unit_cost' => 10,
            'notes_translations' => null,
            'created_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_database_rejects_negative_unit_cost_stock_movements(): void
    {
        $this->seed();

        $product = Product::query()->where('sku', 'PROD-001')->firstOrFail();
        $warehouse = Warehouse::query()->where('code', 'MAIN')->firstOrFail();

        $this->expectException(QueryException::class);

        DB::table('stock_movements')->insert([
            'uuid' => (string) Str::uuid(),
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'movement_type' => 'adjustment',
            'source_type' => null,
            'source_id' => null,
            'reference_no' => 'INT-SM-NEG-COST',
            'quantity' => 2,
            'balance_after' => 102,
            'unit_cost' => -10,
            'notes_translations' => null,
            'created_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_database_rejects_inconsistent_source_link_fields(): void
    {
        $this->seed();

        $product = Product::query()->where('sku', 'PROD-001')->firstOrFail();
        $warehouse = Warehouse::query()->where('code', 'MAIN')->firstOrFail();

        $this->expectException(QueryException::class);

        DB::table('stock_movements')->insert([
            'uuid' => (string) Str::uuid(),
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'movement_type' => 'adjustment',
            'source_type' => 'App\\Modules\\Sales\\Models\\SalesDelivery',
            'source_id' => null,
            'reference_no' => 'INT-SM-SOURCE',
            'quantity' => 1,
            'balance_after' => 101,
            'unit_cost' => 10,
            'notes_translations' => null,
            'created_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

