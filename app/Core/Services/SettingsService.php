<?php

namespace App\Core\Services;

use App\Core\Models\Setting;
use Illuminate\Support\Collection;

class SettingsService
{
    public function get(string $group, string $key, mixed $default = null): mixed
    {
        $setting = Setting::query()
            ->where('group', $group)
            ->where('key', $key)
            ->first();

        return $setting?->typed_value ?? $default;
    }

    public function put(string $group, string $key, mixed $value, bool $isPublic = false): Setting
    {
        return Setting::query()->updateOrCreate(
            ['group' => $group, 'key' => $key],
            [
                'value' => $value,
                'type' => get_debug_type($value),
                'is_public' => $isPublic,
            ],
        );
    }

    public function group(string $group): Collection
    {
        return Setting::query()
            ->forGroup($group)
            ->get()
            ->mapWithKeys(fn (Setting $setting): array => [$setting->key => $setting->typed_value]);
    }
}
