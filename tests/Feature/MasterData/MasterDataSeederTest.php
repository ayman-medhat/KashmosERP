<?php

namespace Tests\Feature\MasterData;

use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\Product;
use App\Modules\MasterData\Models\ProductCategory;
use App\Modules\MasterData\Models\Supplier;
use App\Modules\MasterData\Models\Tax;
use App\Modules\MasterData\Models\Unit;
use App\Modules\MasterData\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_data_seed_records_are_created(): void
    {
        $this->seed();

        $this->assertDatabaseHas('units', ['code' => 'PCS']);
        $this->assertDatabaseHas('taxes', ['code' => 'VAT14']);
        $this->assertDatabaseHas('warehouses', ['code' => 'MAIN']);
        $this->assertDatabaseHas('customers', ['code' => 'CUST-001']);
        $this->assertDatabaseHas('suppliers', ['code' => 'SUP-001']);
        $this->assertDatabaseHas('products', ['sku' => 'PROD-001']);

        $this->assertSame('General', ProductCategory::query()->first()?->name);
        $this->assertInstanceOf(Unit::class, Unit::query()->first());
        $this->assertInstanceOf(Tax::class, Tax::query()->first());
        $this->assertInstanceOf(Product::class, Product::query()->first());
        $this->assertInstanceOf(Customer::class, Customer::query()->first());
        $this->assertInstanceOf(Supplier::class, Supplier::query()->first());
        $this->assertInstanceOf(Warehouse::class, Warehouse::query()->first());
    }
}
