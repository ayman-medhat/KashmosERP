<?php

namespace Tests\Feature\MasterData;

use App\Core\Services\SettingsService;
use App\Modules\MasterData\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_blocks_negative_stock_when_setting_is_disabled(): void
    {
        $this->seed();

        app(SettingsService::class)->put('inventory', 'allow_negative_stock', false);

        $service = app(ProductService::class);

        $this->expectException(\DomainException::class);
        $service->ensureStockChangeAllowed(-101, 100);
    }

    public function test_it_allows_negative_stock_when_setting_is_enabled(): void
    {
        $this->seed();

        app(SettingsService::class)->put('inventory', 'allow_negative_stock', true);

        $service = app(ProductService::class);

        $service->ensureStockChangeAllowed(-101, 100);
        $this->assertTrue(true);
    }
}
