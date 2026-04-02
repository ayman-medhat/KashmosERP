<?php

namespace Tests\Feature\Core;

use App\Core\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_and_reads_typed_settings(): void
    {
        $service = app(SettingsService::class);

        $service->put('inventory', 'allow_negative_stock', false);
        $service->put('branding', 'app_name', 'Kashmos ERP', true);

        $this->assertFalse($service->get('inventory', 'allow_negative_stock'));
        $this->assertSame('Kashmos ERP', $service->get('branding', 'app_name'));
        $this->assertSame([
            'app_name' => 'Kashmos ERP',
        ], $service->group('branding')->all());
    }
}
