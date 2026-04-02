<?php

namespace App\Core\Models;

class Permission extends \Spatie\Permission\Models\Permission
{
    protected $fillable = [
        'name',
        'display_name',
        'module',
        'guard_name',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'bool',
        ];
    }
}
