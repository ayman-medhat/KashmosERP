<?php

namespace Tests\Feature\Core;

use App\Core\Models\ThemePreset;
use App\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class ApplicationContextMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_applies_locale_direction_and_theme_from_user_preferences(): void
    {
        ThemePreset::query()->create([
            'key' => 'blue',
            'name' => 'Blue',
            'mode' => 'system',
            'palette' => ['500' => '#2563eb'],
            'is_default' => true,
        ]);

        $user = User::factory()->create([
            'locale' => 'en',
        ]);

        $user->preference()->create([
            'locale' => 'ar',
            'theme_mode' => 'dark',
            'theme_key' => 'blue',
            'sidebar_collapsed' => false,
        ]);

        Route::middleware('web')->get('/__context-test', function () {
            return response()->json([
                'locale' => app()->getLocale(),
                'context' => View::shared('kashmosContext'),
            ]);
        });

        $response = $this->actingAs($user)->get('/__context-test');

        $response->assertOk();
        $response->assertJsonPath('locale', 'ar');
        $response->assertJsonPath('context.direction', 'rtl');
        $response->assertJsonPath('context.theme_key', 'blue');
        $response->assertJsonPath('context.theme_mode', 'dark');
    }
}
