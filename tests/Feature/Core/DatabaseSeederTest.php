<?php

namespace Tests\Feature\Core;

use App\Core\Models\CompanyProfile;
use App\Core\Models\Role;
use App\Core\Models\ThemePreset;
use App\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_seeder_creates_kashmos_admin_foundation(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'kashmos@outlook.com')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('super-admin'));
        $this->assertDatabaseHas('settings', [
            'group' => 'branding',
            'key' => 'app_name',
        ]);
        $this->assertSame(3, ThemePreset::query()->count());
        $this->assertInstanceOf(CompanyProfile::class, CompanyProfile::query()->first());
        $this->assertDatabaseHas('roles', [
            'name' => 'super-admin',
        ]);
    }
}
