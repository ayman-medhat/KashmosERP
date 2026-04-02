<?php

namespace App\Modules\MasterData\Models;

use App\Modules\MasterData\Models\Concerns\HasTranslatedName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tax extends Model
{
    use HasTranslatedName;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'code',
        'name_translations',
        'rate',
        'is_inclusive',
        'is_active',
    ];

    protected $casts = [
        'name_translations' => 'array',
        'rate' => 'decimal:4',
        'is_inclusive' => 'bool',
        'is_active' => 'bool',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
