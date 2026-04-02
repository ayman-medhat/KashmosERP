<?php

namespace App\Core\Models;

class Role extends \Spatie\Permission\Models\Role
{
    protected $fillable = [
        'name',
        'display_name',
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
