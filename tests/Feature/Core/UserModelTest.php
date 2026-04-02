<?php

namespace Tests\Feature\Core;

use App\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_uuid_is_generated_when_creating_user_without_uuid(): void
    {
        $user = User::query()->create([
            'name' => 'Kashmos User',
            'email' => 'uuid-autogen@kashmos.test',
            'password' => 'password',
            'locale' => 'en',
            'is_active' => true,
        ]);

        $this->assertNotNull($user->uuid);
        $this->assertTrue(Str::isUuid($user->uuid));
    }
}

