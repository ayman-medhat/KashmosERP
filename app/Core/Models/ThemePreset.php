<?php

namespace App\Core\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'name', 'mode', 'palette', 'is_default'])]
class ThemePreset extends Model
{
    protected $casts = [
        'palette' => 'array',
        'is_default' => 'bool',
    ];
}
