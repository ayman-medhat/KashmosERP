<?php

namespace App\Core\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['group', 'key', 'value', 'type', 'is_public'])]
class Setting extends Model
{
    protected $casts = [
        'value' => 'array',
        'is_public' => 'bool',
    ];

    public function scopeForGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    public function getTypedValueAttribute(): mixed
    {
        return match ($this->type) {
            'bool', 'boolean' => (bool) $this->value,
            'int', 'integer' => (int) $this->value,
            'float', 'double' => (float) $this->value,
            'array' => $this->value ?? [],
            default => $this->value,
        };
    }
}
