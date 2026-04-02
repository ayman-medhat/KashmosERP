<?php

namespace App\Modules\MasterData\Models;

use App\Modules\MasterData\Models\Concerns\HasTranslatedName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use HasTranslatedName;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'code',
        'name_translations',
        'precision',
        'is_active',
    ];

    protected $casts = [
        'name_translations' => 'array',
        'precision' => 'int',
        'is_active' => 'bool',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
