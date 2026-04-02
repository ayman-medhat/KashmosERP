<?php

namespace Tests\Feature\CRM;

use App\Core\Models\User;
use App\Modules\CRM\Models\CrmReportPreset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmReportPresetTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_create_and_load_presets(): void
    {
        $user = User::factory()->create();

        $preset = CrmReportPreset::create([
            'user_id' => $user->id,
            'name' => 'Monthly Sales',
            'type' => 'conversion',
            'filters' => [
                'date_from' => '2026-01-01',
                'date_to' => '2026-01-31',
            ],
        ]);

        $this->assertDatabaseHas('crm_report_presets', [
            'name' => 'Monthly Sales',
            'user_id' => $user->id,
        ]);

        $this->assertEquals('2026-01-01', $preset->filters['date_from']);
    }
}
